<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['inventory_id', 'purchase_batch_id', 'job_card_id', 'type', 'quantity', 'cost_price', 'notes'])]
class StockMovement extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'cost_price' => 'decimal:2',
        ];
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function purchaseBatch()
    {
        return $this->belongsTo(PurchaseBatch::class);
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }
}
