<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Vehicle;
use App\Models\TrackerUser;
use App\Models\TrackerVehicle;
use App\Models\TrackerFuelLog;
use App\Models\TrackerExpenseLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackerSyncController extends Controller
{
    /**
     * Validate the shared TRACKER_API_KEY.
     */
    private function validateApiKey(Request $request)
    {
        $header = $request->header('Authorization') ?: $request->header('X-Api-Key');
        $key = str_replace('Bearer ', '', $header);
        $expected = config('services.tracker.api_key');

        if (empty($expected) || $key !== $expected) {
            return false;
        }
        return true;
    }

    /**
     * Handle OPTIONS preflight for CORS.
     */
    public function options()
    {
        return response()->json([], 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization, X-Api-Key');
    }

    /**
     * Register or sync a new/existing client from the Tracker app.
     */
    public function newClient(Request $request)
    {
        if ($request->isMethod('options')) {
            return $this->options();
        }

        if (!$this->validateApiKey($request)) {
            return response()->json(['error' => 'Unauthorized'], 401)
                ->header('Access-Control-Allow-Origin', '*');
        }

        $name = $request->input('name');
        $phone = $request->input('phone');
        $email = $request->input('email');
        $trackerUserId = $request->input('tracker_user_id');

        if (empty($phone) || empty($name) || empty($trackerUserId)) {
            return response()->json(['error' => 'Missing parameters. name, phone, and tracker_user_id are required.'], 400)
                ->header('Access-Control-Allow-Origin', '*');
        }

        // Normalize phone to digits suffix
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $phoneSuffix = substr($cleanPhone, -9);

        // Find existing client in Laravel
        $clients = Client::all();
        $client = null;

        foreach ($clients as $c) {
            $cleanDbPhone = preg_replace('/[^0-9]/', '', $c->phone);
            if (strlen($cleanDbPhone) >= 9 && strlen($cleanPhone) >= 9 && substr($cleanDbPhone, -9) === $phoneSuffix) {
                $client = $c;
                break;
            }
        }

        if ($client) {
            // Update existing client
            $client->tracker_user_id = $trackerUserId;
            if (empty($client->email) && !empty($email)) {
                $client->email = $email;
            }
            $client->save();
        } else {
            // Create a brand new client profile
            $client = Client::create([
                'name' => $name,
                'phone' => $phone,
                'email' => $email ?: null,
                'address' => null,
                'tracker_user_id' => $trackerUserId,
            ]);
        }

        // Push client + vehicle list back to Tracker for immediate synchronization
        self::pushClientToTracker($client);

        return response()->json([
            'success' => true,
            'client_id' => $client->id,
            'is_new' => $client->wasRecentlyCreated
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Update a vehicle's mileage/odometer from a tracker refuel or service log.
     */
    public function updateOdometer(Request $request)
    {
        if ($request->isMethod('options')) {
            return $this->options();
        }

        if (!$this->validateApiKey($request)) {
            return response()->json(['error' => 'Unauthorized'], 401)
                ->header('Access-Control-Allow-Origin', '*');
        }

        $plateNumber = $request->input('plate_number');
        $odometer = (int) $request->input('odometer');

        if (empty($plateNumber) || empty($odometer)) {
            return response()->json(['error' => 'Missing parameters. plate_number and odometer are required.'], 400)
                ->header('Access-Control-Allow-Origin', '*');
        }

        $cleanPlateInput = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plateNumber));

        $vehicles = Vehicle::all();
        $vehicle = null;

        foreach ($vehicles as $v) {
            $cleanDbPlate = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $v->plate_number));
            if ($cleanDbPlate === $cleanPlateInput) {
                $vehicle = $v;
                break;
            }
        }

        if ($vehicle) {
            if ($odometer > ($vehicle->mileage ?: 0)) {
                $vehicle->mileage = $odometer;
                $vehicle->save();
            }
            return response()->json(['success' => true])->header('Access-Control-Allow-Origin', '*');
        }

        return response()->json(['error' => 'Vehicle not found.'], 404)
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Push client and vehicle array to the Tracker API.
     */
    public static function pushClientToTracker(Client $client)
    {
        $trackerUrl = config('services.tracker.management_url') ?: 'https://tdc-tracker.netlify.app';
        $apiKey = config('services.tracker.api_key');

        if (empty($apiKey)) return;

        $client->load('vehicles');

        $vehiclesData = [];
        foreach ($client->vehicles as $veh) {
            $vehiclesData[] = [
                'id' => $veh->id,
                'make' => $veh->make,
                'model' => $veh->model,
                'year' => $veh->year,
                'plate_number' => $veh->plate_number,
                'mileage' => $veh->mileage ?: 0,
            ];
        }

        try {
            Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$trackerUrl}/api/sync/ingest-clients", [
                'clients' => [
                    [
                        'id' => $client->id,
                        'name' => $client->name,
                        'phone' => $client->phone,
                        'email' => $client->email,
                        'vehicles' => $vehiclesData,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to push client sync to Tracker: ' . $e->getMessage());
        }
    }

    /**
     * Render the Tracker Telemetry Dashboard.
     */
    public function telemetryIndex()
    {
        abort_unless(auth()->user()->hasModuleAccess('telemetry'), 403);

        $lastSyncSetting = \App\Models\Setting::where('key', 'tracker_last_sync_at')->first();
        $lastSyncTime = $lastSyncSetting ? (int)$lastSyncSetting->value : null;

        $syncedVehiclesCount = \App\Models\TrackerVehicle::where('is_tdc_verified', true)->count();
        $pendingVehiclesCount = \App\Models\TrackerVehicle::where('is_tdc_verified', false)->count();
        $totalUsersCount = \App\Models\TrackerUser::count();

        // Calculate total expenditure across all tracker data
        $fuelTotal = \App\Models\TrackerFuelLog::sum('total_cost') ?: 0;
        $expenseTotal = \App\Models\TrackerExpenseLog::sum('amount') ?: 0;
        $totalExpenditure = $fuelTotal + $expenseTotal;

        // Fetch pending vehicle approvals with their owner profiles
        $pendingVehicles = \App\Models\TrackerVehicle::where('is_tdc_verified', false)
            ->with('user')
            ->get()
            ->map(function ($veh) {
                // Pre-match with a local client in the core CRM based on 9-digit phone suffix
                $matchedClient = null;
                if ($veh->user) {
                    $cleanPhone = preg_replace('/[^0-9]/', '', $veh->user->phone);
                    $suffix = substr($cleanPhone, -9);
                    if ($suffix) {
                        $matchedClient = \App\Models\Client::where('phone', 'like', '%' . $suffix)->first();
                    }
                }
                $veh->matched_client = $matchedClient;
                return $veh;
            });

        // Fetch all verified tracker vehicles with their logs
        $verifiedVehicles = \App\Models\TrackerVehicle::where('is_tdc_verified', true)
            ->with(['user', 'fuelLogs', 'expenseLogs'])
            ->get()
            ->map(function ($veh) {
                // Find standard Laravel client
                $veh->matched_client = \App\Models\Client::where('tracker_user_id', $veh->tracker_user_id)->first();
                $veh->fuel_economy = $veh->getFuelEconomy();
                $veh->total_spent = $veh->getTotalExpenditure();
                return $veh;
            });

        return view('telemetry.index', compact(
            'lastSyncTime',
            'syncedVehiclesCount',
            'pendingVehiclesCount',
            'totalUsersCount',
            'totalExpenditure',
            'pendingVehicles',
            'verifiedVehicles'
        ));
    }

    /**
     * Pull manual telemetry updates from the companion app.
     */
    public function telemetrySync()
    {
        abort_unless(auth()->user()->hasModuleAccess('telemetry'), 403);

        $trackerUrl = config('services.tracker.management_url') ?: 'https://tdc-tracker.netlify.app';
        $apiKey = config('services.tracker.api_key');

        if (empty($apiKey)) {
            return back()->with('error', 'Tracker API Key is not configured in environment.');
        }

        $sinceSetting = \App\Models\Setting::where('key', 'tracker_last_sync_at')->first();
        $since = $sinceSetting ? (int)$sinceSetting->value : 0;

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->get("{$trackerUrl}/api/telemetry", [
                'since' => $since
            ]);

            if ($response->failed()) {
                return back()->with('error', 'Failed to retrieve telemetry data from Netlify. Status: ' . $response->status());
            }

            $data = $response->json();

            $usersCount = 0;
            $vehiclesCount = 0;
            $fuelCount = 0;
            $expenseCount = 0;

            // 1. Process Users
            if (isset($data['users']) && is_array($data['users'])) {
                foreach ($data['users'] as $u) {
                    \App\Models\TrackerUser::updateOrCreate(
                        ['id' => $u['id']],
                        [
                            'phone' => $u['phone'],
                            'email' => $u['email'] ?? null,
                            'first_name' => $u['firstName'] ?? 'Customer',
                            'last_name' => $u['lastName'] ?? '',
                        ]
                    );
                    $usersCount++;
                }
            }

            // 2. Process Vehicles
            if (isset($data['vehicles']) && is_array($data['vehicles'])) {
                foreach ($data['vehicles'] as $v) {
                    \App\Models\TrackerVehicle::updateOrCreate(
                        ['id' => $v['id']],
                        [
                            'tracker_user_id' => $v['userId'],
                            'make' => $v['make'] ?? 'Unknown',
                            'model' => $v['model'] ?? 'Unknown',
                            'year' => $v['year'] ?? null,
                            'plate_number' => $v['plateNumber'],
                            'default_fuel_type' => $v['defaultFuelType'] ?? 'Petrol 92',
                            'current_odometer' => $v['currentOdometer'] ?? 0,
                            'is_tdc_verified' => (bool)($v['isTdcVerified'] ?? false),
                            'tdc_vehicle_id' => $v['tdcVehicleId'] ?? null,
                        ]
                    );
                    $vehiclesCount++;
                }
            }

            // 3. Process Fuel Logs
            if (isset($data['fuel_logs']) && is_array($data['fuel_logs'])) {
                foreach ($data['fuel_logs'] as $fl) {
                    \App\Models\TrackerFuelLog::updateOrCreate(
                        ['id' => $fl['id']],
                        [
                            'tracker_vehicle_id' => $fl['vehicleId'],
                            'odometer_km' => $fl['odometerKm'],
                            'fuel_type' => $fl['fuelType'],
                            'liters' => $fl['liters'] ?? null,
                            'price_per_liter' => $fl['pricePerLiter'] ?? null,
                            'total_cost' => $fl['totalCost'] ?? null,
                            'notes' => $fl['notes'] ?? null,
                            'logged_at' => $fl['loggedAt'],
                        ]
                    );
                    $fuelCount++;
                }
            }

            // 4. Process Expense Logs
            if (isset($data['expense_logs']) && is_array($data['expense_logs'])) {
                foreach ($data['expense_logs'] as $el) {
                    \App\Models\TrackerExpenseLog::updateOrCreate(
                        ['id' => $el['id']],
                        [
                            'tracker_vehicle_id' => $el['trackerVehicleId'] ?? $el['vehicleId'],
                            'odometer_km' => $el['odometerKm'],
                            'category' => $el['category'],
                            'amount' => $el['amount'],
                            'notes' => $el['notes'] ?? null,
                            'logged_at' => $el['loggedAt'],
                        ]
                    );
                    $expenseCount++;
                }
            }

            // Save the last synced timestamp
            \App\Models\Setting::updateOrCreate(
                ['key' => 'tracker_last_sync_at'],
                ['value' => time()]
            );

            return back()->with('success', "Telemetry data synced successfully! Processed {$usersCount} users, {$vehiclesCount} vehicles, {$fuelCount} fuel logs, and {$expenseCount} expenses.");
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed with error: ' . $e->getMessage());
        }
    }

    /**
     * Approve a client-added vehicle and link it to the core CRM database.
     */
    public function telemetryApprove($id, Request $request)
    {
        abort_unless(auth()->user()->hasModuleAccess('telemetry'), 403);

        $trackerVehicle = \App\Models\TrackerVehicle::findOrFail($id);
        $trackerUser = $trackerVehicle->user;

        if (!$trackerUser) {
            return back()->with('error', 'Cannot approve vehicle: owner profile not synced yet.');
        }

        // 1. Locate or create standard Client in CRM
        $cleanPhone = preg_replace('/[^0-9]/', '', $trackerUser->phone);
        $suffix = substr($cleanPhone, -9);
        $client = null;

        if ($suffix) {
            $client = \App\Models\Client::where('phone', 'like', '%' . $suffix)->first();
        }

        if ($client) {
            // Update client's tracker link and email
            $client->tracker_user_id = $trackerUser->id;
            if (empty($client->email) && !empty($trackerUser->email)) {
                $client->email = $trackerUser->email;
            }
            $client->save();
        } else {
            // Register a new CRM customer profile
            $client = \App\Models\Client::create([
                'name' => trim($trackerUser->first_name . ' ' . $trackerUser->last_name),
                'phone' => $trackerUser->phone,
                'email' => $trackerUser->email ?: null,
                'address' => null,
                'tracker_user_id' => $trackerUser->id,
            ]);
        }

        // 2. Locate or create standard Vehicle in CRM
        $cleanPlateInput = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $trackerVehicle->plate_number));
        $laravelVehicle = \App\Models\Vehicle::all()->first(function($v) use ($cleanPlateInput) {
            return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $v->plate_number)) === $cleanPlateInput;
        });

        if ($laravelVehicle) {
            // Link existing vehicle
            $laravelVehicle->client_id = $client->id;
            if ($trackerVehicle->current_odometer > ($laravelVehicle->mileage ?: 0)) {
                $laravelVehicle->mileage = $trackerVehicle->current_odometer;
            }
            $laravelVehicle->save();
        } else {
            // Create a new vehicle record
            $laravelVehicle = \App\Models\Vehicle::create([
                'client_id' => $client->id,
                'make' => $trackerVehicle->make,
                'model' => $trackerVehicle->model,
                'year' => $trackerVehicle->year ?: date('Y'),
                'plate_number' => $trackerVehicle->plate_number,
                'vin' => null,
                'mileage' => $trackerVehicle->current_odometer ?: 0,
            ]);
        }

        // 3. Push approval response back to the Tracker API
        $trackerUrl = config('services.tracker.management_url') ?: 'https://tdc-tracker.netlify.app';
        $apiKey = config('services.tracker.api_key');

        if ($apiKey) {
            try {
                Http::withHeaders([
                    'X-Api-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$trackerUrl}/api/telemetry", [
                    'action' => 'approve',
                    'vehicleId' => $trackerVehicle->id,
                    'tdcVehicleId' => $laravelVehicle->id,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to push vehicle approval to Tracker: ' . $e->getMessage());
            }
        }

        // 4. Update the local cache state
        $trackerVehicle->update([
            'is_tdc_verified' => true,
            'tdc_vehicle_id' => $laravelVehicle->id,
        ]);

        return back()->with('success', "Vehicle '{$trackerVehicle->plate_number}' has been approved and linked to customer profile '{$client->name}'!");
    }
}
