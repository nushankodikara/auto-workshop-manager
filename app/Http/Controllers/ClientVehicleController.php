<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientVehicleController extends Controller
{
    /**
     * List all clients.
     */
    public function clientsIndex(Request $request)
    {
        $clients = Client::withCount('vehicles')->latest()->get();
        return view('clients.index', compact('clients'));
    }

    /**
     * Show client details and linked vehicles.
     */
    public function clientShow(Client $client)
    {
        $client->load('vehicles.jobCards');
        return view('clients.show', compact('client'));
    }

    public function clientStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $client = Client::create($data);

        // Automatically sync to Tracker
        \App\Http\Controllers\TrackerSyncController::pushClientToTracker($client);

        return redirect()->route('clients.show', $client)->with('success', 'Client profile created successfully.');
    }

    /**
     * Update client details.
     */
    public function clientUpdate(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $client->update($data);

        // Automatically sync to Tracker
        \App\Http\Controllers\TrackerSyncController::pushClientToTracker($client);

        return back()->with('success', 'Client profile updated successfully.');
    }

    /**
     * List all vehicles.
     */
    public function vehiclesIndex(Request $request)
    {
        $vehicles = Vehicle::with(['client', 'jobCards'])->latest()->get();
        $clients = Client::orderBy('name')->get();
        return view('clients.vehicles', compact('vehicles', 'clients'));
    }

    /**
     * Store a new vehicle linked to a client.
     */
    public function vehicleStore(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'make' => 'required|string|max:50',
            'model' => 'required|string|max:50',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'required|string|max:20',
            'vin' => 'nullable|string|max:50',
            'mileage' => 'nullable|integer|min:0',
        ]);

        $veh = Vehicle::create($data);

        // Automatically sync to Tracker
        if ($veh->client) {
            \App\Http\Controllers\TrackerSyncController::pushClientToTracker($veh->client);
        }

        return back()->with('success', 'Vehicle registered successfully.');
    }

    /**
     * Update vehicle details.
     */
    public function vehicleUpdate(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'make' => 'required|string|max:50',
            'model' => 'required|string|max:50',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'required|string|max:20',
            'vin' => 'nullable|string|max:50',
            'mileage' => 'nullable|integer|min:0',
        ]);

        $vehicle->update($data);

        // Automatically sync to Tracker
        if ($vehicle->client) {
            \App\Http\Controllers\TrackerSyncController::pushClientToTracker($vehicle->client);
        }

        return back()->with('success', 'Vehicle details updated successfully.');
    }

    /**
     * Show vehicle repair and services history report.
     */
    public function vehicleHistory(Vehicle $vehicle, Request $request)
    {
        $vehicle->load([
            'client',
            'jobCards' => function ($query) {
                $query->with(['services', 'workers', 'shop', 'bill.items', 'stockMovements.inventory', 'stockMovements.purchaseBatch'])->latest();
            }
        ]);

        $showPrices = $request->query('show_prices', 1);

        return view('clients.history', compact('vehicle', 'showPrices'));
    }

    /**
     * Delete a client and their vehicles.
     */
    public function clientDestroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client profile deleted.');
    }

    /**
     * Delete a vehicle.
     */
    public function vehicleDestroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return back()->with('success', 'Vehicle record deleted.');
    }

    /**
     * Sync a single client to the Tracker.
     */
    public function clientSync(Client $client)
    {
        \App\Http\Controllers\TrackerSyncController::pushClientToTracker($client);

        return back()->with('success', 'Client profile and vehicles synced to TDC Tracker successfully.');
    }

    /**
     * Sync all clients to the Tracker.
     */
    public function clientsSyncAll()
    {
        $clients = Client::all();
        $count = 0;
        foreach ($clients as $client) {
            \App\Http\Controllers\TrackerSyncController::pushClientToTracker($client);
            $count++;
        }

        return back()->with('success', "Successfully synced {$count} client profiles and vehicles to TDC Tracker.");
    }

    /**
     * Show all duplicate client groups (same phone number, multiple records).
     */
    public function clientsDuplicates()
    {
        // Find phone numbers that appear more than once
        $duplicatePhones = Client::select('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone');

        // Load all clients in those groups with vehicle and job card counts
        $groups = $duplicatePhones->map(function ($phone) {
            return Client::where('phone', $phone)
                ->withCount(['vehicles', 'jobCards'])
                ->oldest()
                ->get();
        });

        $totalDuplicates = $groups->sum(fn($g) => $g->count() - 1);

        return view('clients.duplicates', compact('groups', 'totalDuplicates'));
    }

    /**
     * Merge duplicate client records into one primary record.
     * Reassigns all vehicles, then hard-deletes the duplicates.
     */
    public function clientsMerge(Request $request)
    {
        $request->validate([
            'primary_id'      => 'required|exists:clients,id',
            'duplicate_ids'   => 'required|array|min:1',
            'duplicate_ids.*' => 'exists:clients,id|different:primary_id',
        ]);

        $primaryId    = $request->input('primary_id');
        $duplicateIds = $request->input('duplicate_ids');

        DB::transaction(function () use ($primaryId, $duplicateIds) {
            // Re-assign all vehicles from duplicates to the primary client
            Vehicle::whereIn('client_id', $duplicateIds)
                ->update(['client_id' => $primaryId]);

            // Hard-delete the duplicate shells
            Client::whereIn('id', $duplicateIds)->delete();
        });

        $mergedCount = count($duplicateIds);

        return redirect()->route('clients.duplicates')
            ->with('success', "Merged {$mergedCount} duplicate record(s) successfully. All vehicles have been reassigned to the primary client.");
    }

    /**
     * Show all duplicate vehicle groups (same plate number, multiple records).
     */
    public function vehiclesDuplicates()
    {
        // Find plate numbers that appear more than once (case-insensitive)
        $duplicatePlates = Vehicle::selectRaw('upper(plate_number) as plate')
            ->groupByRaw('upper(plate_number)')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('plate');

        // Load all vehicles in those groups with client details and job card counts
        $groups = $duplicatePlates->map(function ($plate) {
            return Vehicle::whereRaw('upper(plate_number) = ?', [$plate])
                ->with(['client'])
                ->withCount(['jobCards'])
                ->oldest()
                ->get();
        });

        $totalDuplicates = $groups->sum(fn($g) => $g->count() - 1);

        return view('vehicles.duplicates', compact('groups', 'totalDuplicates'));
    }

    /**
     * Merge duplicate vehicle records into one primary record.
     * Reassigns all job cards, appointments, and telemetry links, then deletes duplicates.
     */
    public function vehiclesMerge(Request $request)
    {
        $request->validate([
            'primary_id'      => 'required|exists:vehicles,id',
            'duplicate_ids'   => 'required|array|min:1',
            'duplicate_ids.*' => 'exists:vehicles,id|different:primary_id',
        ]);

        $primaryId    = $request->input('primary_id');
        $duplicateIds = $request->input('duplicate_ids');

        DB::transaction(function () use ($primaryId, $duplicateIds) {
            // 1. Reassign job cards
            \App\Models\JobCard::whereIn('vehicle_id', $duplicateIds)
                ->update(['vehicle_id' => $primaryId]);

            // 2. Reassign appointments
            \App\Models\Appointment::whereIn('vehicle_id', $duplicateIds)
                ->update(['vehicle_id' => $primaryId]);

            // 3. Reassign tracker telemetry links
            \App\Models\TrackerVehicle::whereIn('tdc_vehicle_id', $duplicateIds)
                ->update(['tdc_vehicle_id' => $primaryId]);

            // 4. Delete the duplicate vehicle records
            Vehicle::whereIn('id', $duplicateIds)->delete();
        });

        $mergedCount = count($duplicateIds);

        return redirect()->route('vehicles.duplicates')
            ->with('success', "Merged {$mergedCount} duplicate vehicle record(s) successfully. All job cards, appointments, and telemetry links have been reassigned to the primary vehicle.");
    }
}
