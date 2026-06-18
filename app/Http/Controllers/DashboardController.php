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

        // Low stock items (less than 10)
        $lowStockItems = Inventory::where('quantity', '<', 10)->get();

        // Paid billing total this month
        $monthlyRevenue = Bill::where('status', 'paid')
            ->whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->sum('total_amount');

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
            'monthlyRevenue',
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

        // 3. Low stock inventory summary
        $lowStockCount = DB::table('inventory')->where('quantity', '<', 10)->count();

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
}
