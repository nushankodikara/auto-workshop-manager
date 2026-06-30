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
        // Restrict to super-manager
        if (!Auth::user()->isSuperManager()) {
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
        if (!Auth::user()->isSuperManager()) {
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

        // Total Expenses query (Type: Expense accounts)
        $expenditureQuery = \App\Models\JournalItem::whereHas('account', function ($q) {
            $q->where('type', 'expense');
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
        $totalExpenditure = (double)$expenditureQuery->sum('debit');

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
        $paidBasicSalaries = (double)$salariesQuery->sum('debit');
        $paidAllowances = 0.00;
        $totalPayroll = $paidBasicSalaries;

        // Balance of expenses goes to parts stock purchases and other expenditures (COGS, Rent, Utilities, General Expenses)
        $totalStockPurchases = max(0, $totalExpenditure - $totalPayroll);
        $netProfit = $totalIncome - $totalExpenditure;

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

        // Parts
        $partsRevenue = (clone $billItemsQuery)->where('type', 'part')->sum('total_price');
        $partsCOGS = (clone $billItemsQuery)->where('type', 'part')->sum(DB::raw('quantity * cost_price'));
        $partsProfit = $partsRevenue - $partsCOGS;
        $partsMargin = $partsRevenue > 0 ? ($partsProfit / $partsRevenue) * 100 : 0;

        // Labor
        $laborRevenue = (clone $billItemsQuery)->where('type', 'labor')->sum('total_price');
        $laborCOGS = (clone $billItemsQuery)->where('type', 'labor')->sum('cost_price');
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

        return view('dashboard.statistics', compact(
            'startDate', 'endDate',
            'totalIncome', 'totalStockPurchases', 'paidBasicSalaries', 'paidAllowances', 'totalPayroll',
            'totalExpenditure', 'netProfit',
            'partsRevenue', 'partsCOGS', 'partsProfit', 'partsMargin',
            'laborRevenue', 'laborCOGS', 'laborProfit', 'laborMargin',
            'outsourcingRevenue', 'outsourcingCOGS', 'outsourcingProfit', 'outsourcingMargin',
            'tradingRevenue', 'tradingCOGS', 'tradingProfit', 'tradingMargin'
        ));
    }
}
