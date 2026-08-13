<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PayrollCategory;
use App\Models\PayrollSlip;
use App\Models\PayrollSlipItem;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    /**
     * List payroll slips, employee attendance summary, and directory.
     */
    public function index(Request $request)
    {
        $year = (int)$request->input('year', date('Y'));
        $month = (int)$request->input('month', date('m'));

        $slips = PayrollSlip::where('year', $year)
            ->where('month', $month)
            ->with(['user', 'advances'])
            ->latest()
            ->get();

        $users = User::where('is_archived', false)->orderBy('name')->get();
        $archivedUsers = User::where('is_archived', true)->orderBy('name')->get();
        $categories = PayrollCategory::all();
        $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));

        // Load attendance summary for the grid
        $attendanceData = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('user_id');

        $endDate = sprintf('%04d-%02d-%02d', $year, $month, date('t', mktime(0, 0, 0, $month, 1, $year)));

        $pendingSummary = \App\Models\EmployeeAdvance::where('advance_date', '<=', $endDate)
            ->where(function($query) use ($year, $month) {
                $query->where('status', 'pending')
                      ->orWhere(function($q) use ($year, $month) {
                          $q->where('status', 'deducted')
                            ->whereHas('payrollSlip', function($sub) use ($year, $month) {
                                $sub->where('year', '>', $year)
                                    ->orWhere(function($sub2) use ($year, $month) {
                                        $sub2->where('year', $year)
                                             ->where('month', '>', $month);
                                    });
                            });
                      });
            })
            ->select('user_id', 
                DB::raw("SUM(CASE WHEN type = 'benefit' THEN amount ELSE 0 END) as pending_benefits"),
                DB::raw("SUM(CASE WHEN type = 'salary' OR type IS NULL THEN amount ELSE 0 END) as pending_salaries"),
                DB::raw("SUM(amount) as total_pending")
            )
            ->groupBy('user_id')
            ->with('user')
            ->get();

        $advances = \App\Models\EmployeeAdvance::with('user')
            ->orderBy('advance_date', 'desc')
            ->get();

        return view('payroll.index', compact('slips', 'users', 'archivedUsers', 'categories', 'year', 'month', 'daysInMonth', 'attendanceData', 'advances', 'pendingSummary'));
    }

    /**
     * Show salary slip creation form/workspace for a specific worker.
     */
    public function createWorkspace(User $user, Request $request)
    {
        $year = (int)$request->input('year', date('Y'));
        $month = (int)$request->input('month', date('m'));

        // Check if slip already exists
        $existingSlip = PayrollSlip::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existingSlip) {
            return redirect()->route('payroll.show', $existingSlip->id);
        }

        // Calculate actual attendance metrics
        $presentDays = Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', 'present')
            ->count();

        $halfDays = Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', 'half_day')
            ->count();

        $attendedDays = $presentDays + ($halfDays * 0.5);

        $overtimeHours = Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('overtime_hours');

        $requiredDays = $user->required_days ?: 26;
        $overtimeRate = $user->overtime_rate ?: 0.00;

        // Base salary prorated calculation (uses basic_salary)
        $proratedSalary = $requiredDays > 0 ? round(($attendedDays / $requiredDays) * $user->basic_salary, 2) : $user->basic_salary;
        $overtimeAmount = round($overtimeHours * $overtimeRate, 2);

        // Allowances difference calculation
        $baseAllowance = max(0.00, floatval($user->total_salary) - floatval($user->basic_salary));

        $categories = PayrollCategory::all();
        
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = sprintf('%04d-%02d-%02d', $year, $month, date('t', mktime(0, 0, 0, $month, 1, $year)));

        $pendingSalaryAdvancesSum = floatval(
            $user->pendingAdvances()
                ->where('advance_date', '>=', $startDate)
                ->where('advance_date', '<=', $endDate)
                ->where(function($q) {
                    $q->where('type', 'salary')->orWhereNull('type');
                })
                ->sum('amount')
        );
        $pendingBenefitAdvances = $user->pendingAdvances()
            ->where('advance_date', '>=', $startDate)
            ->where('advance_date', '<=', $endDate)
            ->where('type', 'benefit')
            ->get();

        // Check if there is a previous salary slip to copy structure from
        $previousSlip = PayrollSlip::where('user_id', $user->id)
            ->where(function($query) use ($year, $month) {
                $query->where('year', '<', $year)
                      ->orWhere(function($q) use ($year, $month) {
                          $q->where('year', $year)
                            ->where('month', '<', $month);
                      });
            })
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        if ($previousSlip) {
            $prevItems = $previousSlip->items;

            // Additions
            $additions = $prevItems->where('type', 'addition')->map(function($item) {
                return [
                    'name' => $item->category_name,
                    'amount' => $item->amount
                ];
            })->values()->all();

            // Deductions (excluding 'Advance Payment')
            $deductions = $prevItems->where('type', 'deduction')
                ->where('category_name', '!=', 'Advance Payment')
                ->map(function($item) {
                    return [
                        'name' => $item->category_name,
                        'amount' => $item->amount
                    ];
                })->values()->all();

            // Always ensure 'Advance Payment' deduction is present
            $deductions[] = [
                'name' => 'Advance Payment',
                'amount' => $pendingSalaryAdvancesSum > 0 ? $pendingSalaryAdvancesSum : null
            ];

            // Benefits (excluding prepaid benefit advances)
            $benefits = $prevItems->where('type', 'benefit')
                ->filter(function($item) {
                    return !str_starts_with($item->category_name, 'Prepaid Benefit:');
                })
                ->map(function($item) {
                    return [
                        'name' => $item->category_name,
                        'amount' => $item->amount
                    ];
                })->values()->all();
        } else {
            // Default categories if no previous slip exists
            $additions = [];
            if ($baseAllowance > 0) {
                $additions[] = [
                    'name' => 'Base Allowance',
                    'amount' => $baseAllowance
                ];
            }
            foreach ($categories->where('type', 'addition') as $cat) {
                $additions[] = [
                    'name' => $cat->name,
                    'amount' => $cat->default_amount
                ];
            }

            // Deductions
            $deductions = [];
            foreach ($categories->where('type', 'deduction') as $cat) {
                $amount = $cat->default_amount;
                if ($cat->name === 'Advance Payment') {
                    $amount = $pendingSalaryAdvancesSum > 0 ? $pendingSalaryAdvancesSum : null;
                }
                $deductions[] = [
                    'name' => $cat->name,
                    'amount' => $amount
                ];
            }

            // Benefits
            $benefits = [];
            foreach ($categories->where('type', 'benefit') as $cat) {
                $benefits[] = [
                    'name' => $cat->name,
                    'amount' => $cat->default_amount
                ];
            }
        }

        return view('payroll.create', compact(
            'user', 'categories', 'year', 'month', 'attendedDays', 'requiredDays', 
            'overtimeHours', 'overtimeRate', 'proratedSalary', 'overtimeAmount', 
            'baseAllowance', 'pendingSalaryAdvancesSum', 'pendingBenefitAdvances',
            'additions', 'deductions', 'benefits'
        ));
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
            'required_days' => 'required|integer|min:0',
            'attended_days' => 'required|numeric|min:0',
            'overtime_hours' => 'required|numeric|min:0',
            'overtime_rate' => 'required|numeric|min:0',
            'overtime_amount' => 'required|numeric|min:0',
            'prorated_salary' => 'required|numeric|min:0',
            'total_salary' => 'required|numeric|min:0',
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
            $allowanceTotal = 0.00;
            $deductionTotal = 0.00;
            $benefitTotal = 0.00;

            $slip = PayrollSlip::create([
                'user_id' => $user->id,
                'month' => $data['month'],
                'year' => $data['year'],
                'basic_salary' => $user->basic_salary,
                'total_salary' => $data['total_salary'],
                'required_days' => $data['required_days'],
                'attended_days' => $data['attended_days'],
                'overtime_hours' => $data['overtime_hours'],
                'overtime_rate' => $data['overtime_rate'],
                'overtime_amount' => $data['overtime_amount'],
                'prorated_salary' => $data['prorated_salary'],
                'allowance' => 0.00, // Temp
                'deductions' => 0.00, // Temp
                'company_benefits' => 0.00, // Temp
                'net_salary' => $data['prorated_salary'] + $data['overtime_amount'],
                'status' => 'draft'
            ]);

            // Link pending advances within the calendar month to this payroll slip & mark as deducted
            $startDate = sprintf('%04d-%02d-01', $data['year'], $data['month']);
            $endDate = sprintf('%04d-%02d-%02d', $data['year'], $data['month'], date('t', mktime(0, 0, 0, $data['month'], 1, $data['year'])));

            $pendingAdvances = $user->pendingAdvances()
                ->where('advance_date', '>=', $startDate)
                ->where('advance_date', '<=', $endDate)
                ->get();
            foreach ($pendingAdvances as $advance) {
                $advance->update([
                    'status' => 'deducted',
                    'payroll_slip_id' => $slip->id
                ]);
            }

            if (!empty($data['item_name'])) {
                foreach ($data['item_name'] as $key => $name) {
                    if (empty($name)) continue;

                    $type = $data['item_type'][$key] ?? 'addition';
                    $amount = floatval($data['item_amount'][$key] ?? 0.00);

                    PayrollSlipItem::create([
                        'payroll_slip_id' => $slip->id,
                        'category_name' => $name,
                        'type' => $type,
                        'amount' => $amount
                    ]);

                    if ($type === 'addition') {
                        $allowanceTotal += $amount;
                    } elseif ($type === 'benefit') {
                        $benefitTotal += $amount;
                    } else {
                        $deductionTotal += $amount;
                    }
                }
            }

            // Compute net salary
            $netSalary = $data['prorated_salary'] + $data['overtime_amount'] + $allowanceTotal - $deductionTotal;

            $slip->update([
                'allowance' => $allowanceTotal,
                'deductions' => $deductionTotal,
                'company_benefits' => $benefitTotal,
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
     * Show salary slip edit form/workspace.
     */
    public function edit(PayrollSlip $payrollSlip)
    {
        if (!auth()->user()->isSuperManager() && !auth()->user()->hasModuleAccess('payroll')) {
            abort(403, 'Unauthorized');
        }

        $payrollSlip->load(['user', 'items']);
        $user = $payrollSlip->user;
        $categories = PayrollCategory::all();

        return view('payroll.edit', compact('payrollSlip', 'user', 'categories'));
    }

    /**
     * Update salary slip.
     */
    public function update(Request $request, PayrollSlip $payrollSlip)
    {
        if (!auth()->user()->isSuperManager() && !auth()->user()->hasModuleAccess('payroll')) {
            abort(403, 'Unauthorized');
        }

        $data = $request->validate([
            'required_days' => 'required|integer|min:0',
            'attended_days' => 'required|numeric|min:0',
            'overtime_hours' => 'required|numeric|min:0',
            'overtime_rate' => 'required|numeric|min:0',
            'overtime_amount' => 'required|numeric|min:0',
            'prorated_salary' => 'required|numeric|min:0',
            'total_salary' => 'required|numeric|min:0',
            // Allowances/Deductions arrays
            'item_name' => 'nullable|array',
            'item_type' => 'nullable|array',
            'item_amount' => 'nullable|array',
        ]);

        DB::transaction(function () use ($payrollSlip, $data) {
            $allowanceTotal = 0.00;
            $deductionTotal = 0.00;
            $benefitTotal = 0.00;

            // Delete old items
            $payrollSlip->items()->delete();

            if (!empty($data['item_name'])) {
                foreach ($data['item_name'] as $key => $name) {
                    if (empty($name)) continue;

                    $type = $data['item_type'][$key] ?? 'addition';
                    $amount = floatval($data['item_amount'][$key] ?? 0.00);

                    PayrollSlipItem::create([
                        'payroll_slip_id' => $payrollSlip->id,
                        'category_name' => $name,
                        'type' => $type,
                        'amount' => $amount
                    ]);

                    if ($type === 'addition') {
                        $allowanceTotal += $amount;
                    } elseif ($type === 'benefit') {
                        $benefitTotal += $amount;
                    } else {
                        $deductionTotal += $amount;
                    }
                }
            }

            // Compute net salary
            $netSalary = $data['prorated_salary'] + $data['overtime_amount'] + $allowanceTotal - $deductionTotal;

            $payrollSlip->update([
                'total_salary' => $data['total_salary'],
                'required_days' => $data['required_days'],
                'attended_days' => $data['attended_days'],
                'overtime_hours' => $data['overtime_hours'],
                'overtime_rate' => $data['overtime_rate'],
                'overtime_amount' => $data['overtime_amount'],
                'prorated_salary' => $data['prorated_salary'],
                'allowance' => $allowanceTotal,
                'deductions' => $deductionTotal,
                'company_benefits' => $benefitTotal,
                'net_salary' => $netSalary
            ]);

            // Re-post if already marked as paid
            if ($payrollSlip->status === 'paid') {
                \App\Services\DoubleEntryService::postPayrollSlipTransaction($payrollSlip);
            }
        });

        return redirect()->route('payroll.show', $payrollSlip->id)->with('success', 'Salary slip updated successfully.');
    }

    /**
     * Discard / Delete salary slip.
     */
    public function destroy(PayrollSlip $payrollSlip)
    {
        if (!auth()->user()->isSuperManager() && !auth()->user()->hasModuleAccess('payroll')) {
            abort(403, 'Unauthorized');
        }

        DB::transaction(function () use ($payrollSlip) {
            // Delete dynamic DoubleEntry ledger items if it was marked as paid
            if ($payrollSlip->status === 'paid') {
                $oldEntry = \App\Models\JournalEntry::where('reference', 'SLIP-' . $payrollSlip->id)->first();
                if ($oldEntry) {
                    $oldEntry->delete();
                }
            }

            $payrollSlip->delete();
        });

        return redirect()->route('payroll.index', [
            'year' => $payrollSlip->year,
            'month' => $payrollSlip->month
        ])->with('success', 'Salary slip discarded successfully.');
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

        \App\Services\DoubleEntryService::postPayrollSlipTransaction($payrollSlip);

        return back()->with('success', "Salary slip status updated to: {$payrollSlip->status}");
    }

    /**
     * Store daily attendance/overtime logs bulk.
     */
    public function attendanceStore(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array', // user_id => status
            'attendance.*' => 'required|in:present,half_day,absent,leave,n/a',
            'in_time' => 'nullable|array', // user_id => in_time
            'in_time.*' => 'nullable|string',
            'out_time' => 'nullable|array', // user_id => out_time
            'out_time.*' => 'nullable|string',
        ]);

        $date = $data['date'];
        $dateObj = \Carbon\Carbon::parse($date)->startOfDay();

        foreach ($data['attendance'] as $userId => $status) {
            if ($status === 'n/a') {
                Attendance::where('user_id', $userId)->where('date', $dateObj)->delete();
            } else {
                $inTime = $data['in_time'][$userId] ?? null;
                $outTime = $data['out_time'][$userId] ?? null;
                $otHours = 0.00;
                $realStatus = $status;

                if ($status === 'present') {
                    if ($inTime && $outTime) {
                        $hours = (strtotime($outTime) - strtotime($inTime)) / 3600;
                        if ($hours >= 8.0) {
                            $realStatus = 'present';
                        } elseif ($hours >= 4.0) {
                            $realStatus = 'half_day';
                        } else {
                            $realStatus = 'absent';
                        }
                        $otHours = max(0, round($hours - 9.5, 2));
                    } else {
                        $realStatus = 'absent';
                    }
                } else {
                    $inTime = null;
                    $outTime = null;
                }

                Attendance::updateOrCreate(
                    ['user_id' => $userId, 'date' => $dateObj],
                    [
                        'status' => $realStatus,
                        'overtime_hours' => $otHours,
                        'in_time' => $inTime,
                        'out_time' => $outTime
                    ]
                );
            }
        }

        return back()->with('success', "Daily attendance updated successfully for date: {$date}");
    }

    /**
     * Show single employee monthly attendance view for editing.
     */
    public function employeeAttendanceIndex(User $user, Request $request)
    {
        $year = (int)$request->input('year', date('Y'));
        $month = (int)$request->input('month', date('m'));
        
        $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
        
        $records = Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->keyBy(function($item) {
                return $item->date->format('j');
            });

        return view('payroll.employee_attendance', compact('user', 'records', 'year', 'month', 'daysInMonth'));
    }

    /**
     * Store single employee bulk monthly attendance.
     */
    public function employeeAttendanceStore(User $user, Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer',
            'status' => 'required|array', // day => status
            'status.*' => 'required|in:present,half_day,absent,leave,n/a',
            'in_time' => 'nullable|array', // day => in_time
            'in_time.*' => 'nullable|string',
            'out_time' => 'nullable|array', // day => out_time
            'out_time.*' => 'nullable|string',
        ]);

        $year = $data['year'];
        $month = $data['month'];

        foreach ($data['status'] as $day => $status) {
            $dateString = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dateObj = \Carbon\Carbon::parse($dateString)->startOfDay();

            if ($status === 'n/a') {
                Attendance::where('user_id', $user->id)->where('date', $dateObj)->delete();
            } else {
                $inTime = $data['in_time'][$day] ?? null;
                $outTime = $data['out_time'][$day] ?? null;
                $otHours = 0.00;
                $realStatus = $status;

                if ($status === 'present') {
                    if ($inTime && $outTime) {
                        $hours = (strtotime($outTime) - strtotime($inTime)) / 3600;
                        if ($hours >= 8.0) {
                            $realStatus = 'present';
                        } elseif ($hours >= 4.0) {
                            $realStatus = 'half_day';
                        } else {
                            $realStatus = 'absent';
                        }
                        $otHours = max(0, round($hours - 9.5, 2));
                    } else {
                        $realStatus = 'absent';
                    }
                } else {
                    $inTime = null;
                    $outTime = null;
                }

                Attendance::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $dateObj],
                    [
                        'status' => $realStatus,
                        'overtime_hours' => $otHours,
                        'in_time' => $inTime,
                        'out_time' => $outTime
                    ]
                );
            }
        }

        return redirect()->route('payroll.index', ['year' => $year, 'month' => $month])
            ->with('success', "Monthly attendance for {$user->name} saved successfully.");
    }

    /**
     * Store a new employee.
     */
    public function employeeStore(Request $request)
    {
        if (!auth()->user()->isSuperManager() && !auth()->user()->hasModuleAccess('payroll')) {
            abort(403, 'Unauthorized');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|exists:roles,name',
            'basic_salary' => 'required|numeric|min:0',
            'total_salary' => 'required|numeric|min:0',
            'required_days' => 'required|integer|min:1|max:31',
            'overtime_rate' => 'required|numeric|min:0',
            'contact_number' => 'nullable|string|max:30',
        ]);

        if ($data['role'] === 'super-manager' && !auth()->user()->isSuperManager()) {
            return back()->withErrors(['role' => 'Only the super admin can create a super admin account.']);
        }

        $data['password'] = bcrypt($data['password']);
        
        $roleRecord = \App\Models\Role::where('name', $data['role'])->first();
        $data['allowed_modules'] = $roleRecord ? $roleRecord->allowed_modules : [];


        User::create($data);

        return back()->with('success', 'Employee profile created successfully.');
    }

    /**
     * Update employee profile.
     */
    public function employeeUpdate(Request $request, User $user)
    {
        if (!auth()->user()->isSuperManager() && !auth()->user()->hasModuleAccess('payroll')) {
            abort(403, 'Unauthorized');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|exists:roles,name',
            'basic_salary' => 'required|numeric|min:0',
            'total_salary' => 'required|numeric|min:0',
            'required_days' => 'required|integer|min:1|max:31',
            'overtime_rate' => 'required|numeric|min:0',
            'password' => 'nullable|string|min:6',
            'contact_number' => 'nullable|string|max:30',
        ]);


        if (!empty($data['password'])) {
            if (!auth()->user()->isSuperManager()) {
                return back()->withErrors(['password' => 'Only the super admin is authorized to reset passwords.']);
            }
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        if ($user->role !== $data['role']) {
            if ($user->role === 'super-manager' || $data['role'] === 'super-manager') {
                if (!auth()->user()->isSuperManager()) {
                    return back()->withErrors(['role' => 'Only the super admin can modify super admin roles.']);
                }
            }
        }

        $roleRecord = \App\Models\Role::where('name', $data['role'])->first();
        $data['allowed_modules'] = $roleRecord ? $roleRecord->allowed_modules : [];

        $user->update($data);

        return back()->with('success', 'Employee profile updated successfully.');
    }


    /**
     * Delete an employee.
     */
    public function employeeDestroy(User $user)
    {
        if (!auth()->user()->isSuperManager() && !auth()->user()->hasModuleAccess('payroll')) {
            abort(403, 'Unauthorized');
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete yourself.']);
        }

        if ($user->role === 'super-manager' && !auth()->user()->isSuperManager()) {
            return back()->withErrors(['error' => 'Only the super admin can delete a super admin account.']);
        }

        $user->delete();

        return back()->with('success', 'Employee profile deleted.');
    }


    /**
     * Show employee profile with ticket utilization, active working hours, overtime, and attendance calendar.
     */
    public function employeeShow(User $user, Request $request)
    {
        $selectedYear = (int)$request->input('year', date('Y'));

        // Fetch all assignments for this employee
        $assignments = \App\Models\JobCardAssignment::where('user_id', $user->id)
            ->with('jobCard.vehicle')
            ->get();

        $ticketBreakdown = [];
        $totalRegularSeconds = 0;
        $totalOvertimeSeconds = 0;

        foreach ($assignments as $assignment) {
            $regSec = $assignment->getActiveSeconds();
            $otSec = $assignment->getOvertimeSeconds();

            $totalRegularSeconds += $regSec;
            $totalOvertimeSeconds += $otSec;

            $jc = $assignment->jobCard;
            if (!$jc) continue;

            $jcId = $jc->id;
            if (!isset($ticketBreakdown[$jcId])) {
                $ticketBreakdown[$jcId] = [
                    'job_card' => $jc,
                    'regular_seconds' => 0,
                    'overtime_seconds' => 0,
                ];
            }
            $ticketBreakdown[$jcId]['regular_seconds'] += $regSec;
            $ticketBreakdown[$jcId]['overtime_seconds'] += $otSec;
        }

        $totalActiveHours = round($totalRegularSeconds / 3600, 2);
        $totalOvertimeHours = round($totalOvertimeSeconds / 3600, 2);

        // Convert seconds to hours for breakdown
        foreach ($ticketBreakdown as $jcId => $data) {
            $ticketBreakdown[$jcId]['regular_hours'] = round($data['regular_seconds'] / 3600, 2);
            $ticketBreakdown[$jcId]['overtime_hours'] = round($data['overtime_seconds'] / 3600, 2);
            $ticketBreakdown[$jcId]['total_hours'] = round(($data['regular_seconds'] + $data['overtime_seconds']) / 3600, 2);
        }

        // Fetch yearly attendance
        $yearlyAttendance = Attendance::where('user_id', $user->id)
            ->whereYear('date', $selectedYear)
            ->get()
            ->keyBy(function ($record) {
                return is_string($record->date) ? substr($record->date, 0, 10) : $record->date->format('Y-m-d');
            });

        return view('payroll.employee_show', compact('user', 'totalActiveHours', 'totalOvertimeHours', 'ticketBreakdown', 'yearlyAttendance', 'selectedYear'));
    }

    /**
     * Archive an employee.
     */
    public function employeeArchive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['archive' => 'You cannot archive your own user account.']);
        }

        $user->update(['is_archived' => true]);

        return redirect()->route('payroll.index')->with('success', 'Employee archived successfully.');
    }

    /**
     * Restore/Unarchive an employee.
     */
    public function employeeUnarchive(User $user)
    {
        $user->update(['is_archived' => false]);

        return redirect()->route('payroll.index')->with('success', 'Employee restored successfully.');
    }

    /**
     * Store employee advance payment.
     */
    public function storeAdvance(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'nullable|string|in:salary,benefit',
            'amount' => 'required|numeric|min:1',
            'advance_date' => 'required|date',
            'reason' => 'nullable|string|max:1000'
        ]);

        $advance = DB::transaction(function () use ($data) {
            $type = $data['type'] ?? 'salary';
            $advance = \App\Models\EmployeeAdvance::create([
                'user_id' => $data['user_id'],
                'type' => $type,
                'amount' => floatval($data['amount']),
                'advance_date' => $data['advance_date'],
                'reason' => $data['reason'] ?? null,
                'status' => 'pending'
            ]);

            \App\Services\DoubleEntryService::postEmployeeAdvanceTransaction($advance);

            return $advance;
        });

        $label = ($advance->type === 'benefit') ? 'Paid benefit advance' : 'Salary advance';
        return back()->with('success', $label . ' of ' . config('app.currency', 'Rs.') . number_format($advance->amount, 2) . ' recorded and posted to ledger.');
    }

    /**
     * Cancel/Delete employee advance payment.
     */
    public function destroyAdvance(\App\Models\EmployeeAdvance $advance)
    {
        if ($advance->status === 'deducted') {
            return back()->withErrors(['advance' => 'Cannot delete an advance that has already been deducted from a salary slip.']);
        }

        DB::transaction(function () use ($advance) {
            $advance->update(['status' => 'cancelled']);
            \App\Services\DoubleEntryService::postEmployeeAdvanceTransaction($advance);
            $advance->delete();
        });

        return back()->with('success', 'Salary advance cancelled and matching ledger entry cleared.');
    }

    /**
     * Export / Print monthly attendance summary.
     */
    public function printAttendanceSummary(Request $request)
    {
        $year = (int)$request->input('year', date('Y'));
        $month = (int)$request->input('month', date('m'));

        $users = User::where('is_archived', false)->orderBy('name')->get();
        $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));

        // Load attendance data
        $attendanceData = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('user_id');

        $startDateStr = sprintf('%04d-%02d-01', $year, $month);
        $endDateStr = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
        
        $advancesThisMonth = \App\Models\EmployeeAdvance::whereBetween('advance_date', [$startDateStr, $endDateStr])
            ->where('status', '!=', 'cancelled')
            ->select('user_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('user_id')
            ->get()
            ->pluck('total_amount', 'user_id');

        $preparedBy = Auth::user() ? Auth::user()->name : 'System Administrator';

        $managers = $users->filter(function($u) {
            return in_array($u->role, ['manager', 'super-manager']);
        });

        return view('payroll.print_attendance', compact(
            'users', 'year', 'month', 'daysInMonth', 'attendanceData', 'advancesThisMonth', 'preparedBy', 'managers'
        ));
    }
}
