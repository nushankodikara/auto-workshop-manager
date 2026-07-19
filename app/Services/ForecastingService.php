<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ForecastingService
{
    /**
     * Forecast demand for next 30 days using Holt's Linear (Double) Exponential Smoothing
     * 
     * @param string $tableName Table name ('stock_movements' or 'consumable_usages')
     * @param string $foreignKey Foreign key column name ('inventory_id' or 'consumable_id')
     * @param int $itemId Item ID
     * @param int $weeksCount Number of weeks of history to analyze (default 12)
     * @return float Predicted demand for next 30 days (4 weeks)
     */
    public static function forecast30DaysDemand(string $tableName, string $foreignKey, int $itemId, int $weeksCount = 12): float
    {
        // 1. Retrieve weekly usage data for the last $weeksCount weeks
        $weeklyUsage = [];
        $qtyColumn = 'quantity';
        if ($tableName === 'consumable_usages') {
            $qtyColumn = 'quantity_consumed';
        }
        
        for ($i = $weeksCount - 1; $i >= 0; $i--) {
            $start = Carbon::now()->subWeeks($i + 1)->startOfWeek();
            $end = Carbon::now()->subWeeks($i)->startOfWeek();

            $query = DB::table($tableName)
                ->where($foreignKey, $itemId)
                ->whereBetween('created_at', [$start, $end]);

            if ($tableName === 'stock_movements') {
                $usage = $query->where('quantity', '<', 0)->sum('quantity');
                $weeklyUsage[] = abs(floatval($usage));
            } else {
                $usage = $query->sum($qtyColumn);
                $weeklyUsage[] = floatval($usage);
            }
        }

        // 2. Holt's Linear Exponential Smoothing calculations
        $n = count($weeklyUsage);
        
        // If series has too few non-zero points or is very short, fallback to simple weighted moving average
        $nonZeroCount = collect($weeklyUsage)->filter(fn($val) => $val > 0)->count();
        if ($n < 3 || $nonZeroCount < 2) {
            // Weighted average fallback (recent weeks count more)
            $totalWeight = 0;
            $weightedSum = 0;
            foreach ($weeklyUsage as $index => $usage) {
                $weight = $index + 1; // higher weight for recent weeks
                $weightedSum += $usage * $weight;
                $totalWeight += $weight;
            }
            $weeklyAverage = $totalWeight > 0 ? ($weightedSum / $totalWeight) : 0;
            return max(0.00, $weeklyAverage * 4.285); // 30 days = ~4.285 weeks
        }

        // Initialize Level (L) and Trend (T)
        // Alpha (level smoothing) and Beta (trend smoothing)
        $alpha = 0.35;
        $beta = 0.15;

        $level = $weeklyUsage[0];
        // Calculate initial trend across first few items to prevent volatility
        $trend = 0.0;
        for ($j = 1; $j < min(4, $n); $j++) {
            $trend += ($weeklyUsage[$j] - $weeklyUsage[$j - 1]);
        }
        $trend = $trend / min(3, $n - 1);

        // Process data points
        for ($t = 1; $t < $n; $t++) {
            $lastLevel = $level;
            $level = $alpha * $weeklyUsage[$t] + (1 - $alpha) * ($level + $trend);
            $trend = $beta * ($level - $lastLevel) + (1 - $beta) * $trend;
        }

        // Forecast next 4.285 weeks (30 days)
        $forecastTotal = 0;
        for ($m = 1; $m <= 4; $m++) {
            $weekForecast = $level + $m * $trend;
            $forecastTotal += max(0.00, $weekForecast);
        }
        // Add fractional week (0.285 weeks)
        $fractionalForecast = $level + 4.285 * $trend;
        $forecastTotal += max(0.00, $fractionalForecast * 0.285);

        return round($forecastTotal, 2);
    }
}
