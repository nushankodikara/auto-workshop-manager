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

        // 1. Total Cash In (Revenue accounts credit)
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

        // 2. Expenditure Components (Cash Out):
        // A. Parts Inventory Purchases (Code 1300 debit)
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

        // B. Salaries & Wages (Code 5100 debit)
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

        // C. General Operating Expenses (type 'expense', code != 5000 and code != 5100)
        $operatingExpensesQuery = \App\Models\JournalItem::whereHas('account', function ($q) {
            $q->where('type', 'expense')->whereNotIn('code', ['5000', '5100']);
        });
        if ($startDate) {
            $operatingExpensesQuery->whereHas('entry', function ($q) use ($startDate) {
                $q->whereDate('entry_date', '>=', $startDate);
            });
        }
        if ($endDate) {
            $operatingExpensesQuery->whereHas('entry', function ($q) use ($endDate) {
                $q->whereDate('entry_date', '<=', $endDate);
            });
        }
        $totalOperatingExpenses = (double)$operatingExpensesQuery->sum('debit');

        // D. Direct/Misc Parts COGS & Outsourcing COGS from Paid Bills
        $billItemsQuery = \App\Models\BillItem::whereHas('bill', function ($q) use ($startDate, $endDate) {
            $q->where('status', 'paid');
            if ($startDate) {
                $q->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $q->whereDate('created_at', '<=', $endDate);
            }
        });

        $miscPartsCOGS = floatval((clone $billItemsQuery)->where('type', 'part')->whereNull('inventory_id')->sum(DB::raw('quantity * cost_price')));
        $outsourcingCOGS = floatval((clone $billItemsQuery)->where('type', 'outsourcing')->sum('cost_price'));

        // Combined Total Expenditure (Cash Out)
        $totalExpenditure = $totalStockPurchases + $totalPayroll + $totalOperatingExpenses + $miscPartsCOGS + $outsourcingCOGS;
        $netProfit = $totalIncome - $totalExpenditure;

        // Grouped Operating Expenditures by Account
        $expendituresByAccount = \App\Models\JournalItem::join('accounts', 'journal_items.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.type', 'expense')
            ->whereNotIn('accounts.code', ['5000', '5100'])
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('journal_entries.entry_date', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('journal_entries.entry_date', '<=', $endDate);
            })
            ->select('accounts.name', 'accounts.code', DB::raw('SUM(journal_items.debit) as total'), DB::raw('COUNT(DISTINCT journal_entries.id) as count'))
            ->groupBy('accounts.id', 'accounts.name', 'accounts.code')
            ->orderBy('total', 'desc')
            ->get();

        // Structured Expenditure Breakdown
        $expenditureBreakdown = [
            [
                'category' => 'Direct Job Parts (Misc COGS)',
                'code' => '5000',
                'description' => 'Direct and dealer parts purchased specifically for billed client job cards',
                'amount' => $miscPartsCOGS,
                'percentage' => $totalExpenditure > 0 ? ($miscPartsCOGS / $totalExpenditure) * 100 : 0,
                'icon' => 'wrench'
            ],
            [
                'category' => 'Outsourcing & Sublet Services',
                'code' => '5000',
                'description' => 'External specialized lathe, machining, and sublet repair services',
                'amount' => $outsourcingCOGS,
                'percentage' => $totalExpenditure > 0 ? ($outsourcingCOGS / $totalExpenditure) * 100 : 0,
                'icon' => 'external-link'
            ],
            [
                'category' => 'Parts Inventory Stock Purchases',
                'code' => '1300',
                'description' => 'Stock replenishment and warehouse inventory purchase batches',
                'amount' => $totalStockPurchases,
                'percentage' => $totalExpenditure > 0 ? ($totalStockPurchases / $totalExpenditure) * 100 : 0,
                'icon' => 'boxes'
            ],
            [
                'category' => 'Staff Salaries & Wages',
                'code' => '5100',
                'description' => 'Technician basic salaries, overtime, and salary payouts',
                'amount' => $totalPayroll,
                'percentage' => $totalExpenditure > 0 ? ($totalPayroll / $totalExpenditure) * 100 : 0,
                'icon' => 'users'
            ],
            [
                'category' => 'General Operating Overheads',
                'code' => '5200-5999',
                'description' => 'Rent, utilities, marketing, consumables, tools, and fuel',
                'amount' => $totalOperatingExpenses,
                'percentage' => $totalExpenditure > 0 ? ($totalOperatingExpenses / $totalExpenditure) * 100 : 0,
                'icon' => 'receipt',
                'items' => $expendituresByAccount
            ]
        ];

        // 3. Unified Daily Financial Timeline Aggregation
        // Daily Income
        $dailyIncome = \App\Models\JournalItem::join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereHas('account', function ($q) {
                $q->where('type', 'revenue');
            })
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('journal_entries.entry_date', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('journal_entries.entry_date', '<=', $endDate);
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', journal_entries.entry_date) as date"),
                DB::raw("SUM(journal_items.credit) as value")
            )
            ->groupBy('date')
            ->get()
            ->pluck('value', 'date');

        // Daily Stock Purchases
        $dailyStockPurchases = \App\Models\JournalItem::join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereHas('account', function ($q) {
                $q->where('code', '1300');
            })
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('journal_entries.entry_date', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('journal_entries.entry_date', '<=', $endDate);
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', journal_entries.entry_date) as date"),
                DB::raw("SUM(journal_items.debit) as value")
            )
            ->groupBy('date')
            ->get()
            ->pluck('value', 'date');

        // Daily Payroll
        $dailyPayroll = \App\Models\JournalItem::join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereHas('account', function ($q) {
                $q->where('code', '5100');
            })
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('journal_entries.entry_date', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('journal_entries.entry_date', '<=', $endDate);
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', journal_entries.entry_date) as date"),
                DB::raw("SUM(journal_items.debit) as value")
            )
            ->groupBy('date')
            ->get()
            ->pluck('value', 'date');

        // Daily Operating Expenses
        $dailyOperatingExpenses = \App\Models\JournalItem::join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereHas('account', function ($q) {
                $q->where('type', 'expense')->whereNotIn('code', ['5000', '5100']);
            })
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('journal_entries.entry_date', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('journal_entries.entry_date', '<=', $endDate);
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', journal_entries.entry_date) as date"),
                DB::raw("SUM(journal_items.debit) as value")
            )
            ->groupBy('date')
            ->get()
            ->pluck('value', 'date');

        // Daily Misc Parts COGS
        $dailyMiscParts = \App\Models\BillItem::where('type', 'part')
            ->whereNull('inventory_id')
            ->whereHas('bill', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'paid');
                if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', created_at) as date"),
                DB::raw("SUM(quantity * cost_price) as cogs")
            )
            ->groupBy('date')
            ->get()
            ->pluck('cogs', 'date');

        // Daily Outsourcing COGS
        $dailyOutsourcing = \App\Models\BillItem::where('type', 'outsourcing')
            ->whereHas('bill', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'paid');
                if ($startDate) $q->whereDate('created_at', '>=', $startDate);
                if ($endDate) $q->whereDate('created_at', '<=', $endDate);
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', created_at) as date"),
                DB::raw("SUM(cost_price) as cogs")
            )
            ->groupBy('date')
            ->get()
            ->pluck('cogs', 'date');

        // Daily Jobs Count
        $dailyJobs = \App\Models\JobCard::when($startDate, function($q) use ($startDate) {
                return $q->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('created_at', '<=', $endDate);
            })
            ->select(
                DB::raw("strftime('%Y-%m-%d', created_at) as date"),
                DB::raw("COUNT(*) as count")
            )
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date');

        // Collect all unique dates
        $allDates = collect()
            ->merge($dailyIncome->keys())
            ->merge($dailyStockPurchases->keys())
            ->merge($dailyPayroll->keys())
            ->merge($dailyOperatingExpenses->keys())
            ->merge($dailyMiscParts->keys())
            ->merge($dailyOutsourcing->keys())
            ->merge($dailyJobs->keys())
            ->unique()
            ->sort()
            ->values();

        // Build unified daily timeline (Cash In, Cash Out, Net, Jobs)
        $dailyTimeline = $allDates->map(function ($date) use (
            $dailyIncome, $dailyStockPurchases, $dailyPayroll, $dailyOperatingExpenses, $dailyMiscParts, $dailyOutsourcing, $dailyJobs
        ) {
            $inc = (double)($dailyIncome->get($date) ?? 0.00);
            $stock = (double)($dailyStockPurchases->get($date) ?? 0.00);
            $pay = (double)($dailyPayroll->get($date) ?? 0.00);
            $op = (double)($dailyOperatingExpenses->get($date) ?? 0.00);
            $misc = (double)($dailyMiscParts->get($date) ?? 0.00);
            $out = (double)($dailyOutsourcing->get($date) ?? 0.00);

            $exp = $stock + $pay + $op + $misc + $out;
            $jobsCount = (int)($dailyJobs->get($date) ?? 0);

            return [
                'date' => $date,
                'income' => $inc,
                'expenditure' => $exp,
                'net_profit' => $inc - $exp,
                'jobs' => $jobsCount
            ];
        });

        // Break-Even Target Calculation for the current month until the end of the month
        $currentYear = intval(date('Y'));
        $currentMonth = intval(date('m'));
        $currentDay = intval(date('d'));
        $daysInMonth = intval(date('t'));

        $managementSalaries = \App\Models\User::where('role', '!=', 'worker')
            ->where('is_archived', false)
            ->sum('basic_salary');
        $monthlyUtilities = 80000.00;

        $monthBillItemsQuery = \App\Models\BillItem::whereHas('bill', function ($q) use ($currentYear, $currentMonth) {
            $q->whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->where('status', 'paid');
        });
        $monthMiscPartsCOGS = floatval((clone $monthBillItemsQuery)->where('type', 'part')->whereNull('inventory_id')->sum(DB::raw('quantity * cost_price')));
        $monthOutsourcingCOGS = floatval((clone $monthBillItemsQuery)->where('type', 'outsourcing')->sum('cost_price'));

        $totalMonthCosts = floatval($managementSalaries) + $monthlyUtilities + $monthMiscPartsCOGS + $monthOutsourcingCOGS;
        $totalMonthIncome = floatval(\App\Models\Bill::whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->where('status', 'paid')
            ->sum('total_amount'));

        $remainingBreakEven = max(0.00, $totalMonthCosts - $totalMonthIncome);
        $daysLeft = max(1, $daysInMonth - $currentDay + 1);
        $dailyBreakEvenTarget = $remainingBreakEven / $daysLeft;
        $breakEvenMonthName = date('F');

        // Job Cards Detail View (NO LABOUR)
        $jobCardsQuery = \App\Models\JobCard::with(['vehicle.client', 'bill', 'stockMovements', 'miscParts', 'outsourcingItems']);
        if ($startDate) {
            $jobCardsQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $jobCardsQuery->whereDate('created_at', '<=', $endDate);
        }
        $rawJobCards = $jobCardsQuery->orderBy('created_at', 'desc')->get();

        $jobCardsByDay = $rawJobCards->groupBy(function($jc) {
            return $jc->created_at->format('Y-m-d');
        });

        // Precalculate weighted average costs for consumables
        $weightedAvgCostsForProration = [];
        $consumablesList = \App\Models\Consumable::all();
        foreach ($consumablesList as $item) {
            $totalQty = floatval($item->purchases()->sum('quantity'));
            $totalCost = floatval($item->purchases()->sum('cost_price'));
            $weightedAvgCostsForProration[$item->id] = $totalQty > 0 ? ($totalCost / $totalQty) : 0.00;
        }

        $formattedJobCardsByDay = [];
        foreach ($jobCardsByDay as $dateStr => $dayJobCards) {
            $dateUsages = \App\Models\ConsumableUsage::whereDate('recorded_at', $dateStr)->get();
            $dailyConsumablesCost = 0.00;
            foreach ($dateUsages as $usage) {
                $avgCost = $weightedAvgCostsForProration[$usage->consumable_id] ?? 0.00;
                $dailyConsumablesCost += floatval($usage->quantity_consumed) * $avgCost;
            }
            $jcCount = count($dayJobCards);
            $proratedConsumables = $jcCount > 0 ? ($dailyConsumablesCost / $jcCount) : 0.00;

            $formattedJobs = [];
            foreach ($dayJobCards as $jc) {
                $income = $jc->bill ? floatval($jc->bill->total_amount) : 0.00;

                $partsCostInventory = floatval($jc->stockMovements
                    ->where('type', 'out')
                    ->sum(function($mov) {
                        return abs($mov->quantity) * floatval($mov->cost_price);
                    }));

                $partsCostMisc = floatval($jc->miscParts->sum('cost_price'));
                $outsourcingCost = floatval($jc->outsourcingItems->sum('cost_price'));

                // Notice: Labor is completely removed per user instructions!
                $totalOutgoing = $partsCostInventory + $partsCostMisc + $outsourcingCost + $proratedConsumables;

                $formattedJobs[] = [
                    'id' => $jc->id,
                    'card_number' => $jc->card_number,
                    'status' => $jc->status,
                    'plate_number' => $jc->vehicle->plate_number ?? 'Unknown',
                    'client_name' => $jc->vehicle->client->name ?? 'Unknown',
                    'bill_number' => $jc->bill->bill_number ?? null,
                    'income' => $income,
                    'parts_inventory' => $partsCostInventory,
                    'parts_misc' => $partsCostMisc,
                    'outsourcing' => $outsourcingCost,
                    'consumables' => $proratedConsumables,
                    'outgoing' => $totalOutgoing,
                    'profit' => $income - $totalOutgoing
                ];
            }

            $formattedJobCardsByDay[$dateStr] = $formattedJobs;
        }

        return view('dashboard.statistics', compact(
            'startDate', 'endDate',
            'totalIncome', 'totalExpenditure', 'netProfit',
            'totalStockPurchases', 'totalPayroll', 'totalOperatingExpenses', 'miscPartsCOGS', 'outsourcingCOGS',
            'expenditureBreakdown', 'expendituresByAccount',
            'dailyTimeline',
            'remainingBreakEven', 'daysLeft', 'dailyBreakEvenTarget', 'totalMonthCosts', 'totalMonthIncome', 'breakEvenMonthName',
            'formattedJobCardsByDay'
        ));
    }
}
