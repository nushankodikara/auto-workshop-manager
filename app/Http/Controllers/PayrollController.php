<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PayrollCategory;
use App\Models\PayrollSlip;
use App\Models\PayrollSlipItem;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->with('user')
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

        return view('payroll.index', compact('slips', 'users', 'archivedUsers', 'categories', 'year', 'month', 'daysInMonth', 'attendanceData'));
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

        // Base salary prorated calculation
        $proratedSalary = $requiredDays > 0 ? round(($attendedDays / $requiredDays) * $user->basic_salary, 2) : $user->basic_salary;
        $overtimeAmount = round($overtimeHours * $overtimeRate, 2);

        $categories = PayrollCategory::all();

        return view('payroll.create', compact('user', 'categories', 'year', 'month', 'attendedDays', 'requiredDays', 'overtimeHours', 'overtimeRate', 'proratedSalary', 'overtimeAmount'));
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

            $slip = PayrollSlip::create([
                'user_id' => $user->id,
                'month' => $data['month'],
                'year' => $data['year'],
                'basic_salary' => $user->basic_salary,
                'required_days' => $data['required_days'],
                'attended_days' => $data['attended_days'],
                'overtime_hours' => $data['overtime_hours'],
                'overtime_rate' => $data['overtime_rate'],
                'overtime_amount' => $data['overtime_amount'],
                'prorated_salary' => $data['prorated_salary'],
                'allowance' => 0.00, // Temp
                'deductions' => 0.00, // Temp
                'net_salary' => $data['prorated_salary'] + $data['overtime_amount'],
                'status' => 'draft'
            ]);

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

    /**
     * Store daily attendance/overtime logs bulk.
     */
    public function attendanceStore(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array', // user_id => status
            'attendance.*' => 'required|in:present,half_day,absent,leave,n/a',
            'overtime' => 'nullable|array', // user_id => overtime_hours
            'overtime.*' => 'nullable|numeric|min:0',
        ]);

        $date = $data['date'];
        $dateObj = \Carbon\Carbon::parse($date)->startOfDay();

        foreach ($data['attendance'] as $userId => $status) {
            if ($status === 'n/a') {
                Attendance::where('user_id', $userId)->where('date', $dateObj)->delete();
            } else {
                $otHours = isset($data['overtime'][$userId]) ? floatval($data['overtime'][$userId]) : 0.00;

                Attendance::updateOrCreate(
                    ['user_id' => $userId, 'date' => $dateObj],
                    ['status' => $status, 'overtime_hours' => $otHours]
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
            'overtime' => 'nullable|array', // day => overtime
            'overtime.*' => 'nullable|numeric|min:0',
        ]);

        $year = $data['year'];
        $month = $data['month'];

        foreach ($data['status'] as $day => $status) {
            $dateString = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dateObj = \Carbon\Carbon::parse($dateString)->startOfDay();

            if ($status === 'n/a') {
                Attendance::where('user_id', $user->id)->where('date', $dateObj)->delete();
            } else {
                $ot = isset($data['overtime'][$day]) ? floatval($data['overtime'][$day]) : 0.00;

                Attendance::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $dateObj],
                    ['status' => $status, 'overtime_hours' => $ot]
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
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:manager,worker',
            'basic_salary' => 'required|numeric|min:0',
            'required_days' => 'required|integer|min:1|max:31',
            'overtime_rate' => 'required|numeric|min:0',
        ]);

        $data['password'] = bcrypt($data['password']);
        
        if ($data['role'] === 'manager') {
            $data['allowed_modules'] = ['dashboard', 'job-cards', 'clients', 'inventory', 'billing'];
        } else {
            $data['allowed_modules'] = [];
        }

        User::create($data);

        return back()->with('success', 'Employee profile created successfully.');
    }

    /**
     * Update employee profile.
     */
    public function employeeUpdate(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:manager,worker,super-manager',
            'basic_salary' => 'required|numeric|min:0',
            'required_days' => 'required|integer|min:1|max:31',
            'overtime_rate' => 'required|numeric|min:0',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'Employee profile updated successfully.');
    }

    /**
     * Delete an employee.
     */
    public function employeeDestroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete yourself.']);
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
}
