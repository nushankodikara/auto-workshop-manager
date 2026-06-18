<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PayrollCategory;
use App\Models\PayrollSlip;
use App\Models\PayrollSlipItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * List payroll slips.
     */
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));

        $slips = PayrollSlip::where('year', $year)
            ->where('month', $month)
            ->with('user')
            ->latest()
            ->get();

        $users = User::all();
        $categories = PayrollCategory::all();

        return view('payroll.index', compact('slips', 'users', 'categories', 'year', 'month'));
    }

    /**
     * Show salary slip creation form/workspace for a specific worker.
     */
    public function createWorkspace(User $user, Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));

        // Check if slip already exists
        $existingSlip = PayrollSlip::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existingSlip) {
            return redirect()->route('payroll.show', $existingSlip->id);
        }

        $categories = PayrollCategory::all();

        return view('payroll.create', compact('user', 'categories', 'year', 'month'));
    }

    /**
     * Generate salary slip.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2050',
            // Allowances/Deductions arrays
            'item_name' => 'nullable|array',
            'item_type' => 'nullable|array',
            'item_amount' => 'nullable|array',
        ]);

        $user = User::findOrFail($data['user_id']);

        // Prevent duplicates
        $duplicate = PayrollSlip::where('user_id', $user->id)
            ->where('year', $data['year'])
            ->where('month', $data['month'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['user_id' => 'A salary slip already exists for this user in the specified month/year.']);
        }

        DB::transaction(function () use ($user, $data) {
            $basicSalary = $user->basic_salary;
            $allowanceTotal = 0.00;
            $deductionTotal = 0.00;

            $slip = PayrollSlip::create([
                'user_id' => $user->id,
                'month' => $data['month'],
                'year' => $data['year'],
                'basic_salary' => $basicSalary,
                'allowance' => 0.00, // Temp
                'deductions' => 0.00, // Temp
                'net_salary' => $basicSalary,
                'status' => 'draft'
            ]);

            if (!empty($data['item_name'])) {
                foreach ($data['item_name'] as $key => $name) {
                    if (empty($name)) continue;

                    $type = $data['item_type'][$key] ?? 'addition';
                    $amount = $data['item_amount'][$key] ?? 0.00;

                    PayrollSlipItem::create([
                        'payroll_slip_id' => $slip->id,
                        'category_name' => $name,
                        'type' => $type,
                        'amount' => $amount
                    ]);

                    if ($type === 'addition') {
                        $allowanceTotal += $amount;
                    } else {
                        $deductionTotal += $amount;
                    }
                }
            }

            // Compute net salary
            $netSalary = $basicSalary + $allowanceTotal - $deductionTotal;

            $slip->update([
                'allowance' => $allowanceTotal,
                'deductions' => $deductionTotal,
                'net_salary' => $netSalary
            ]);
        });

        return redirect()->route('payroll.index', [
            'year' => $data['year'],
            'month' => $data['month']
        ])->with('success', 'Salary slip generated successfully.');
    }

    /**
     * Show salary slip details.
     */
    public function show(PayrollSlip $payrollSlip)
    {
        $payrollSlip->load(['user', 'items']);
        return view('payroll.show', compact('payrollSlip'));
    }

    /**
     * Update status (e.g. from draft to paid).
     */
    public function updateStatus(Request $request, PayrollSlip $payrollSlip)
    {
        $data = $request->validate([
            'status' => 'required|in:draft,paid'
        ]);

        $payrollSlip->update($data);

        return back()->with('success', "Salary slip status updated to: {$payrollSlip->status}");
    }
}
