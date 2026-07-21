<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['inventory_id', 'batch_code', 'quantity_received', 'quantity_remaining', 'cost_price', 'selling_price', 'supplier', 'purchased_at'])]
class PurchaseBatch extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($batch) {
            \App\Services\DoubleEntryService::postPurchaseBatchTransaction($batch);
        });

        static::deleted(function ($batch) {
            JournalEntry::where('reference', 'BATCH-' . $batch->id)->delete();
        });
    }

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
