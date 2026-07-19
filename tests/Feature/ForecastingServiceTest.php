<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Services\ForecastingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ForecastingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Inventory $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->part = Inventory::create([
            'name' => 'Suzuki Alto Brake Pads',
            'sku' => 'SUZ-ALT-BP',
            'quantity' => 10,
            'cost_price' => 1500.00,
            'selling_price' => 2550.00,
            'unit' => 'pcs',
            'low_stock_alert_qty' => 5
        ]);
    }

    public function test_forecast_fallback_on_empty_series()
    {
        // No stock movements recorded - should fallback to 0
        $forecast = ForecastingService::forecast30DaysDemand('stock_movements', 'inventory_id', $this->part->id);
        $this->assertEquals(0.00, $forecast);
    }

    public function test_forecast_growing_demand_trend()
    {
        // Record growing usage over weeks:
        // Week 1 (oldest): 2 units used
        // Week 2: 4 units used
        // Week 3: 6 units used
        // Week 4 (recent): 8 units used
        
        $weeks = [
            4 => 2,
            3 => 4,
            2 => 6,
            1 => 8,
        ];

        foreach ($weeks as $weeksAgo => $qty) {
            StockMovement::create([
                'inventory_id' => $this->part->id,
                'quantity' => -$qty, // Outflow
                'type' => 'out',
                'created_at' => Carbon::now()->subWeeks($weeksAgo)->startOfWeek()->addDays(2)
            ]);
        }

        $forecast = ForecastingService::forecast30DaysDemand('stock_movements', 'inventory_id', $this->part->id, 4);
        
        // Since demand is increasing (2 -> 4 -> 6 -> 8), Holt's method should detect a positive trend.
        // The forecast for next 4.285 weeks should be higher than a simple historical average (average is 5).
        $this->assertTrue($forecast > 20.00); // 4 weeks of avg 5 = 20. Positive trend should project > 20.
    }

    public function test_forecast_falling_demand_trend()
    {
        // Record declining usage over weeks:
        // Week 1 (oldest): 10 units used
        // Week 2: 8 units used
        // Week 3: 5 units used
        // Week 4 (recent): 2 units used
        
        $weeks = [
            4 => 10,
            3 => 8,
            2 => 5,
            1 => 2,
        ];

        foreach ($weeks as $weeksAgo => $qty) {
            StockMovement::create([
                'inventory_id' => $this->part->id,
                'quantity' => -$qty, // Outflow
                'type' => 'out',
                'created_at' => Carbon::now()->subWeeks($weeksAgo)->startOfWeek()->addDays(2)
            ]);
        }

        $forecast = ForecastingService::forecast30DaysDemand('stock_movements', 'inventory_id', $this->part->id, 4);
        
        // Since demand is dropping (10 -> 8 -> 5 -> 2), Holt's method should detect a negative trend.
        // The forecast for next 4.285 weeks should be lower than a simple average (average is 6.25, so average total is ~26.8).
        $this->assertTrue($forecast < 22.00); 
    }
}
