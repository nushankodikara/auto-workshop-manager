<?php

namespace App\Http\Controllers;

use App\Models\OutsourcingCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OutsourcingController extends Controller
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
        $companies = OutsourcingCompany::latest()->get();
        return view('outsourcing.index', compact('companies'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:500',
        ]);

        OutsourcingCompany::create($data);

        return back()->with('success', 'Outsourcing partner company registered successfully.');
    }

    public function update(Request $request, OutsourcingCompany $company)
    {
        $this->checkAccess();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:500',
        ]);

        $company->update($data);

        return back()->with('success', 'Outsourcing partner updated successfully.');
    }

    public function destroy(OutsourcingCompany $company)
    {
        $this->checkAccess();
        $company->delete();
        return back()->with('success', 'Outsourcing partner company removed.');
    }
}
