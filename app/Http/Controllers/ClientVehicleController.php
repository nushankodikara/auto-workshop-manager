<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class ClientVehicleController extends Controller
{
    /**
     * List all clients.
     */
    public function clientsIndex(Request $request)
    {
        $search = $request->input('search');
        
        $clients = Client::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        })->withCount('vehicles')->latest()->paginate(15);

        return view('clients.index', compact('clients', 'search'));
    }

    /**
     * Show client details and linked vehicles.
     */
    public function clientShow(Client $client)
    {
        $client->load('vehicles.jobCards');
        return view('clients.show', compact('client'));
    }

    /**
     * Store a new client.
     */
    public function clientStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $client = Client::create($data);

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

        return back()->with('success', 'Client profile updated successfully.');
    }

    /**
     * List all vehicles.
     */
    public function vehiclesIndex(Request $request)
    {
        $search = $request->input('search');
        
        $vehicles = Vehicle::when($search, function ($query) use ($search) {
            $query->where('plate_number', 'like', "%{$search}%")
                  ->orWhere('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
        })->with(['client', 'jobCards'])->latest()->paginate(15);

        $clients = Client::orderBy('name')->get();

        return view('clients.vehicles', compact('vehicles', 'search', 'clients'));
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

        Vehicle::create($data);

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
}
