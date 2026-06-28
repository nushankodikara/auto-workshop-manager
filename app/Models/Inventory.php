<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'sku', 'quantity', 'cost_price', 'selling_price', 'unit', 'low_stock_alert_qty'])]
class Inventory extends Model
{
    protected $table = 'inventory';

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'low_stock_alert_qty' => 'integer',
        ];
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseBatches()
    {
        return $this->hasMany(PurchaseBatch::class);
    }
}
