<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Vehicle;
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
        $this->pushClientToTracker($client);

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
    private function pushClientToTracker(Client $client)
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
}
