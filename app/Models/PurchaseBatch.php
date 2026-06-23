<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['inventory_id', 'batch_code', 'quantity_received', 'quantity_remaining', 'cost_price', 'selling_price', 'supplier', 'purchased_at'])]
class PurchaseBatch extends Model
{
    protected $casts = [
        'quantity_received' => 'integer',
        'quantity_remaining' => 'integer',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'purchased_at' => 'date'
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
