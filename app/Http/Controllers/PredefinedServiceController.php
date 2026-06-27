<?php

namespace App\Http\Controllers;

use App\Models\PredefinedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PredefinedServiceController extends Controller
{
    private function checkAccess()
    {
        if (!Auth::user() || !Auth::user()->isSuperManager()) {
            abort(403, 'Unauthorized module access.');
        }
    }

    public function index()
    {
        $this->checkAccess();
        $services = PredefinedService::latest()->get();
        return view('services.index', compact('services'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        PredefinedService::create($data);

        return back()->with('success', 'Predefined labor service added successfully.');
    }

    public function update(Request $request, PredefinedService $service)
    {
        $this->checkAccess();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $service->update($data);

        return back()->with('success', 'Predefined labor service updated successfully.');
    }

    public function destroy(PredefinedService $service)
    {
        $this->checkAccess();
        $service->delete();
        return back()->with('success', 'Predefined labor service removed.');
    }
}
