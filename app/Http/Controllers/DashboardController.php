<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\JobCard;
use App\Models\Inventory;
use App\Models\Bill;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Counts
        $clientsCount = Client::count();
        $vehiclesCount = Vehicle::count();
        $activeJobCardsCount = JobCard::where('status', '!=', 'waiting-to-pickup')->count();
        
        // Job Cards by Status
        $receivedCount = JobCard::where('status', 'received-vehicle')->count();
        $ongoingCount = JobCard::where('status', 'on-going')->count();
        $blockedCount = JobCard::where('status', 'blocked')->count();
        $testingCount = JobCard::where('status', 'testing')->count();
        $pickupCount = JobCard::where('status', 'waiting-to-pickup')->count();

        // Low stock items (using custom alert thresholds, where threshold > 0 and stock <= threshold)
        $lowStockItems = Inventory::where('low_stock_alert_qty', '>', 0)
            ->whereColumn('quantity', '<=', 'low_stock_alert_qty')
            ->get();

        // Total Job Cards created this month
        $monthlyJobsCount = JobCard::whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->count();

        // Recent workshop activities
        $recentActivities = Activity::with(['user', 'jobCard.vehicle'])
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'clientsCount',
            'vehiclesCount',
            'activeJobCardsCount',
            'receivedCount',
            'ongoingCount',
            'blockedCount',
            'testingCount',
            'pickupCount',
            'lowStockItems',
            'monthlyJobsCount',
            'recentActivities'
        ));
    }

    /**
     * Show Data Insights and custom SQL query tool.
     */
    public function insights(Request $request)
    {
        // Restrict based on module access
        if (!Auth::user()->hasModuleAccess('insights')) {
            abort(403, 'Unauthorized module access.');
        }


        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        // Stats queries
        // 1. Job Card Count by Technician/Worker
        $technicianJobs = DB::table('users')
            ->leftJoin('job_card_worker', 'users.id', '=', 'job_card_worker.user_id')
            ->leftJoin('job_cards', 'job_card_worker.job_card_id', '=', 'job_cards.id')
            ->select('users.name', DB::raw('COUNT(job_cards.id) as job_count'))
            ->where('users.role', '=', 'worker')
            ->groupBy('users.id', 'users.name')
            ->get();

        // 2. Revenue by type (Parts vs Labor)
        $billingStats = DB::table('bill_items')
            ->join('bills', 'bill_items.bill_id', '=', 'bills.id')
            ->select('bill_items.type', DB::raw('SUM(bill_items.total_price) as total_revenue'))
            ->where('bills.status', '=', 'paid')
            ->whereBetween('bills.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('bill_items.type')
            ->get();

        $partsRevenue = 0;
        $laborRevenue = 0;
        foreach ($billingStats as $stat) {
            if ($stat->type === 'part') {
                $partsRevenue = $stat->total_revenue;
            } elseif ($stat->type === 'labor') {
                $laborRevenue = $stat->total_revenue;
            }
        }

        // 3. Low stock inventory summary (using custom alert thresholds)
        $lowStockCount = DB::table('inventory')
            ->where('low_stock_alert_qty', '>', 0)
            ->whereColumn('quantity', '<=', 'low_stock_alert_qty')
            ->count();

        // Custom SQL console processing
        $sqlQuery = $request->input('sql_query');
        $queryResult = null;
        $queryError = null;
        $headers = [];

        if ($request->isMethod('post') && !empty($sqlQuery)) {
            $cleanedQuery = trim($sqlQuery);

            // Security check: Only allow SELECT queries and block dangerous words
            $isSelect = preg_match('/^\s*select\s/i', $cleanedQuery);
            $hasForbiddenWords = preg_match('/\b(insert|update|delete|drop|alter|create|replace|truncate|grant|revoke|vacuum|pragma)\b/i', $cleanedQuery);

            if (!$isSelect) {
                $queryError = 'Security Block: Only read-only SELECT queries are allowed in this dashboard.';
            } elseif ($hasForbiddenWords) {
                $queryError = 'Security Block: Destructive keywords (INSERT, UPDATE, DELETE, etc.) are prohibited.';
            } else {
                try {
                    // Run the custom query safely
                    $rawResults = DB::select($cleanedQuery);
                    
                    if (!empty($rawResults)) {
                        // Extract headers from the first row keys
                        $headers = array_keys((array)$rawResults[0]);
                        // Map each row to array
                        $queryResult = array_map(function ($row) {
                            return (array)$row;
                        }, $rawResults);
                    } else {
                        $queryResult = [];
                    }
                } catch (\Exception $e) {
                    $queryError = 'SQLite Error: ' . $e->getMessage();
                }
            }
        }

        return view('dashboard.insights', compact(
            'startDate',
            'endDate',
            'technicianJobs',
            'partsRevenue',
            'laborRevenue',
            'lowStockCount',
            'sqlQuery',
            'queryResult',
            'queryError',
            'headers'
        ));
    }

    /**
     * Show Statistics and Finance Dashboard.
     */
    public function statistics(Request $request)
    {
        if (!Auth::user()->hasModuleAccess('statistics')) {
            abort(403, 'Unauthorized module access.');
        }


        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Income query (Type: Revenue accounts)
        $incomeQuery = \App\Models\JournalItem::whereHas('account', function ($q) {
            $q->where('type', 'revenue');
        });
        if ($startDate) {
            $incomeQuery->whereHas('entry', function ($q) use ($startDate) {
                $q->whereDate('entry_date', '>=', $startDate);
            });
        }
        if ($endDate) {
            $incomeQuery->whereHas('entry', function ($q) use ($endDate) {
                $q->whereDate('entry_date', '<=', $endDate);
            });
        }
        $totalIncome = (double)$incomeQuery->sum('credit');

        // Stock Purchases (Code 1300 debit)
        $stockPurchasesQuery = \App\Models\JournalItem::whereHas('account', function ($q) {
            $q->where('code', '1300');
        });
        if ($startDate) {
            $stockPurchasesQuery->whereHas('entry', function ($q) use ($startDate) {
                $q->whereDate('entry_date', '>=', $startDate);
            });
        }
        if ($endDate) {
            $stockPurchasesQuery->whereHas('entry', function ($q) use ($endDate) {
                $q->whereDate('entry_date', '<=', $endDate);
            });
        }
        $totalStockPurchases = (double)$stockPurchasesQuery->sum('debit');

        // Total Expenses query (Type: Expense accounts, excluding COGS 5000)
        $expenditureQuery = \App\Models\JournalItem::whereHas('account', function ($q) {
            $q->where('type', 'expense')->where('code', '!=', '5000');
        });
        if ($startDate) {
            $expenditureQuery->whereHas('entry', function ($q) use ($startDate) {
                $q->whereDate('entry_date', '>=', $startDate);
            });
        }
        if ($endDate) {
            $expenditureQuery->whereHas('entry', function ($q) use ($endDate) {
                $q->whereDate('entry_date', '<=', $endDate);
            });
        }
        $expenseTotal = (double)$expenditureQuery->sum('debit');

        // Segment Salaries (Code 5100)
        $salariesQuery = \App\Models\JournalItem::whereHas('account', function ($q) {
            $q->where('code', '5100');
        });
        if ($startDate) {
            $salariesQuery->whereHas('entry', function ($q) use ($startDate) {
                $q->whereDate('entry_date', '>=', $startDate);
            });
        }
        if ($endDate) {
            $salariesQuery->whereHas('entry', function ($q) use ($endDate) {
                $q->whereDate('entry_date', '<=', $endDate);
            });
        }
        $totalPayroll = (double)$salariesQuery->sum('debit');
        $paidBasicSalaries = $totalPayroll;
        $paidAllowances = 0.00;

        // Trading profitability (linked to paid bills in timeframe)
        $billItemsQuery = \App\Models\BillItem::whereHas('bill', function ($q) use ($startDate, $endDate) {
            $q->where('status', 'paid');
            if ($startDate) {
                $q->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $q->whereDate('created_at', '<=', $endDate);
            }
        });

        // Compute miscellaneous parts cost (not in inventory) and outsourcing cost to include in total expenditure
        $miscPartsCOGS = (clone $billItemsQuery)->where('type', 'part')->whereNull('inventory_id')->sum(DB::raw('quantity * cost_price'));
        $outsourcingCOGS = (clone $billItemsQuery)->where('type', 'outsourcing')->sum('cost_price');

        // Combined Total Expenditure = Stock Purchases + all other expenses + misc parts COGS + outsourcing COGS
        $otherExpenses = max(0, $expenseTotal - $totalPayroll);
        $totalExpenditure = $totalStockPurchases + $totalPayroll + $otherExpenses + floatval($miscPartsCOGS) + floatval($outsourcingCOGS);
        $netProfit = $totalIncome - $totalExpenditure;

        // Parts
        $partsRevenue = (clone $billItemsQuery)->where('type', 'part')->sum('total_price');
        $partsCOGS = (clone $billItemsQuery)->where('type', 'part')->sum(DB::raw('quantity * cost_price'));
        $partsProfit = $partsRevenue - $partsCOGS;
        $partsMargin = $partsRevenue > 0 ? ($partsProfit / $partsRevenue) * 100 : 0;

        // Labor
        $laborRevenue = (clone $billItemsQuery)->where('type', 'labor')->sum('total_price');
        
        // Calculate laborCOGS dynamically from worker attendance (excluding managers/super-managers)
        $workers = \App\Models\User::where('role', 'worker')->get();
        $workerIds = $workers->pluck('id');
        $attendancesQuery = \App\Models\Attendance::whereIn('user_id', $workerIds);
        if ($startDate) {
            $attendancesQuery->whereDate('date', '>=', $startDate);
        }
        if ($endDate) {
            $attendancesQuery->whereDate('date', '<=', $endDate);
        }
        $attendances = $attendancesQuery->get();
        
        $workersMap = $workers->keyBy('id');
        $laborCOGS = 0.00;
        foreach ($attendances as $att) {
            $worker = $workersMap->get($att->user_id);
            if ($worker && $worker->basic_salary > 0) {
                $reqDays = max(1, (int)($worker->required_days ?? 26));
                $dailyWage = $worker->basic_salary / $reqDays;
                if ($att->status === 'present') {
                    $laborCOGS += $dailyWage;
                } elseif ($att->status === 'half_day') {
                    $laborCOGS += $dailyWage * 0.5;
                }
            }
        }
        
        $laborProfit = $laborRevenue - $laborCOGS;
        $laborMargin = $laborRevenue > 0 ? ($laborProfit / $laborRevenue) * 100 : 0;

        // Outsourcing
        $outsourcingRevenue = (clone $billItemsQuery)->where('type', 'outsourcing')->sum('total_price');
        $outsourcingCOGS = (clone $billItemsQuery)->where('type', 'outsourcing')->sum('cost_price');
        $outsourcingProfit = $outsourcingRevenue - $outsourcingCOGS;
        $outsourcingMargin = $outsourcingRevenue > 0 ? ($outsourcingProfit / $outsourcingRevenue) * 100 : 0;

        // Total Trading
        $tradingRevenue = $partsRevenue + $laborRevenue + $outsourcingRevenue;
        $tradingCOGS = $partsCOGS + $laborCOGS + $outsourcingCOGS;
        $tradingProfit = $tradingRevenue - $tradingCOGS;
        $tradingMargin = $tradingRevenue > 0 ? ($tradingProfit / $tradingRevenue) * 100 : 0;

        // Unified Daily Financial Timeline Aggregation
        // 1. Daily Ledger Income (revenue accounts)
        $dailyIncome = \App\Models\JournalItem::join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereHas('account', function ($q) {
                $q->where('type', 'revenue');
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', journal_entries.entry_date) as date"),
                DB::raw("SUM(journal_items.credit) as value")
            )
            ->groupBy('date')
            ->get()
            ->pluck('value', 'date');

        // 2. Daily Ledger Expenditure (expense accounts)
        $dailyExpenditure = \App\Models\JournalItem::join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereHas('account', function ($q) {
                $q->where('type', 'expense');
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', journal_entries.entry_date) as date"),
                DB::raw("SUM(journal_items.debit) as value")
            )
            ->groupBy('date')
            ->get()
            ->pluck('value', 'date');

        // 3. Daily Parts Revenue & COGS (paid bills)
        $dailyParts = \App\Models\BillItem::where('type', 'part')
            ->whereHas('bill', function ($q) {
                $q->where('status', 'paid');
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', created_at) as date"),
                DB::raw("SUM(total_price) as revenue"),
                DB::raw("SUM(quantity * cost_price) as cogs")
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // 4. Daily Labor Revenue (paid bills)
        $dailyLaborRev = \App\Models\BillItem::where('type', 'labor')
            ->whereHas('bill', function ($q) {
                $q->where('status', 'paid');
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', created_at) as date"),
                DB::raw("SUM(total_price) as revenue")
            )
            ->groupBy('date')
            ->get()
            ->pluck('revenue', 'date');

        // 5. Daily Labor COGS (based on worker attendance)
        $workers = \App\Models\User::where('role', 'worker')->get();
        $workerIds = $workers->pluck('id');
        $workersMap = $workers->keyBy('id');
        
        $dailyLaborCOGSQuery = \App\Models\Attendance::whereIn('user_id', $workerIds)
            ->select('date', 'user_id', 'status')
            ->get();
            
        $dailyLaborCOGS = [];
        foreach ($dailyLaborCOGSQuery as $att) {
            $dateStr = $att->date->format('Y-m-d');
            $worker = $workersMap->get($att->user_id);
            if ($worker && $worker->basic_salary > 0) {
                $reqDays = max(1, (int)($worker->required_days ?? 26));
                $dailyWage = $worker->basic_salary / $reqDays;
                
                if (!isset($dailyLaborCOGS[$dateStr])) {
                    $dailyLaborCOGS[$dateStr] = 0.00;
                }
                
                if ($att->status === 'present') {
                    $dailyLaborCOGS[$dateStr] += $dailyWage;
                } elseif ($att->status === 'half_day') {
                    $dailyLaborCOGS[$dateStr] += $dailyWage * 0.5;
                }
            }
        }

        // 6. Daily Outsourcing Revenue & COGS (paid bills)
        $dailyOutsourcing = \App\Models\BillItem::where('type', 'outsourcing')
            ->whereHas('bill', function ($q) {
                $q->where('status', 'paid');
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', created_at) as date"),
                DB::raw("SUM(total_price) as revenue"),
                DB::raw("SUM(cost_price) as cogs")
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // 7. Daily Jobs (Job Cards)
        $dailyJobs = \App\Models\JobCard::select(
            DB::raw("strftime('%Y-%m-%d', created_at) as date"),
            DB::raw("COUNT(*) as count")
        )
        ->groupBy('date')
        ->get()
        ->pluck('count', 'date');

        // Grouped General Expenditures Details
        $expendituresByAccount = \App\Models\JournalItem::join('accounts', 'journal_items.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.type', 'expense')
            ->where('accounts.code', '!=', '5000') // Exclude COGS
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('journal_entries.entry_date', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('journal_entries.entry_date', '<=', $endDate);
            })
            ->select('accounts.name', 'accounts.code', DB::raw('SUM(journal_items.debit) as total'))
            ->groupBy('accounts.id', 'accounts.name', 'accounts.code')
            ->get();

        // Collect all unique dates from all daily logs
        $allDates = collect()
            ->merge($dailyIncome->keys())
            ->merge($dailyExpenditure->keys())
            ->merge($dailyParts->keys())
            ->merge($dailyLaborRev->keys())
            ->merge(array_keys($dailyLaborCOGS))
            ->merge($dailyOutsourcing->keys())
            ->merge($dailyJobs->keys())
            ->unique()
            ->sort()
            ->values();

        // Filter unique dates by target bounds
        if ($startDate) {
            $allDates = $allDates->filter(fn($d) => $d >= $startDate);
        }
        if ($endDate) {
            $allDates = $allDates->filter(fn($d) => $d <= $endDate);
        }
        $allDates = $allDates->values();

        // Build unified daily timeline
        $dailyTimeline = $allDates->map(function ($date) use (
            $dailyIncome, $dailyExpenditure, $dailyParts, $dailyLaborRev, $dailyLaborCOGS, $dailyOutsourcing, $dailyJobs
        ) {
            $inc = (double)($dailyIncome->get($date) ?? 0.00);
            $exp = (double)($dailyExpenditure->get($date) ?? 0.00);
            $jobsCount = (int)($dailyJobs->get($date) ?? 0);
            
            $partRev = 0.00;
            $partCogs = 0.00;
            if ($p = $dailyParts->get($date)) {
                $partRev = (double)$p->revenue;
                $partCogs = (double)$p->cogs;
            }
            
            $labRev = (double)($dailyLaborRev->get($date) ?? 0.00);
            $labCogs = (double)($dailyLaborCOGS[$date] ?? 0.00);
            
            $outRev = 0.00;
            $outCogs = 0.00;
            if ($o = $dailyOutsourcing->get($date)) {
                $outRev = (double)$o->revenue;
                $outCogs = (double)$o->cogs;
            }
            
            return [
                'date' => $date,
                'income' => $inc,
                'expenditure' => $exp,
                'jobs' => $jobsCount,
                'parts_revenue' => $partRev,
                'parts_cogs' => $partCogs,
                'labor_revenue' => $labRev,
                'labor_cogs' => $labCogs,
                'outsourcing_revenue' => $outRev,
                'outsourcing_cogs' => $outCogs
            ];
        });

        // Break-Even Target Calculation for the current month until the 30th
        $currentYear = intval(date('Y'));
        $currentMonth = intval(date('m'));
        $currentDay = intval(date('d'));
        
        // 1. Management salaries for the current month
        $managementSalaries = \App\Models\User::where('role', '!=', 'worker')
            ->where('is_archived', false)
            ->sum('basic_salary');

        // 2. Fixed utilities
        $monthlyUtilities = 80000.00;

        // 3. Parts COGS for current month so far (both inventory and misc parts)
        $augustBillItemsQuery = \App\Models\BillItem::whereHas('bill', function ($q) use ($currentYear, $currentMonth) {
            $q->whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth);
        });
        $augustPartsCOGS = floatval((clone $augustBillItemsQuery)->where('type', 'part')->sum(DB::raw('quantity * cost_price')));
        $augustOutsourcingCOGS = floatval((clone $augustBillItemsQuery)->where('type', 'outsourcing')->sum('cost_price'));

        // 4. Labor Cost (technician wages based on attendance in current month so far)
        $workers = \App\Models\User::where('role', 'worker')->get();
        $workerIds = $workers->pluck('id');
        $workersMap = $workers->keyBy('id');
        $augustAttendances = \App\Models\Attendance::whereIn('user_id', $workerIds)
            ->whereYear('date', $currentYear)
            ->whereMonth('date', $currentMonth)
            ->get();
        
        $augustLabourCOGS = 0.00;
        foreach ($augustAttendances as $att) {
            $worker = $workersMap->get($att->user_id);
            if ($worker && $worker->basic_salary > 0) {
                $reqDays = max(1, (int)($worker->required_days ?? 26));
                $dailyWage = floatval($worker->basic_salary) / $reqDays;
                if ($att->status === 'present') {
                    $augustLabourCOGS += $dailyWage;
                } elseif ($att->status === 'half_day') {
                    $augustLabourCOGS += $dailyWage * 0.5;
                }
            }
        }

        // 5. Consumables Cost in current month so far (weighted average cost)
        $weightedAvgCosts = [];
        $consumables = \App\Models\Consumable::all();
        foreach ($consumables as $item) {
            $totalQty = floatval($item->purchases()->sum('quantity'));
            $totalCost = floatval($item->purchases()->sum('cost_price'));
            $weightedAvgCosts[$item->id] = $totalQty > 0 ? ($totalCost / $totalQty) : 0.00;
        }

        $augustUsages = \App\Models\ConsumableUsage::whereYear('recorded_at', $currentYear)
            ->whereMonth('recorded_at', $currentMonth)
            ->get();
        
        $augustConsumablesCost = 0.00;
        foreach ($augustUsages as $usage) {
            $avgCost = $weightedAvgCosts[$usage->consumable_id] ?? 0.00;
            $augustConsumablesCost += floatval($usage->quantity_consumed) * $avgCost;
        }

        // Total Costs for the Month so far + fixed overheads
        $totalAugustCosts = floatval($managementSalaries) + $monthlyUtilities + $augustPartsCOGS + $augustOutsourcingCOGS + $augustLabourCOGS + $augustConsumablesCost;

        // Total Income for current month so far (include all generated bills: both paid and draft)
        $totalAugustIncome = floatval(\App\Models\Bill::whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->sum('total_amount'));

        $remainingBreakEven = max(0.00, $totalAugustCosts - $totalAugustIncome);
        $daysLeft = max(1, 30 - $currentDay + 1);
        $dailyBreakEvenTarget = $remainingBreakEven / $daysLeft;
        $breakEvenMonthName = date('F');

        return view('dashboard.statistics', compact(
            'startDate', 'endDate',
            'totalIncome', 'totalStockPurchases', 'paidBasicSalaries', 'paidAllowances', 'totalPayroll',
            'totalExpenditure', 'netProfit',
            'partsRevenue', 'partsCOGS', 'partsProfit', 'partsMargin',
            'laborRevenue', 'laborCOGS', 'laborProfit', 'laborMargin',
            'outsourcingRevenue', 'outsourcingCOGS', 'outsourcingProfit', 'outsourcingMargin',
            'tradingRevenue', 'tradingCOGS', 'tradingProfit', 'tradingMargin',
            'dailyTimeline', 'expendituresByAccount',
            'remainingBreakEven', 'daysLeft', 'dailyBreakEvenTarget', 'totalAugustCosts', 'totalAugustIncome', 'breakEvenMonthName'
        ));
    }
}
