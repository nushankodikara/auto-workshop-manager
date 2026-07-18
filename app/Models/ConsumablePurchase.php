<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['consumable_id', 'batch_code', 'quantity', 'cost_price', 'supplier', 'purchased_at', 'payment_method', 'journal_entry_id'])]
class ConsumablePurchase extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        static::deleted(function ($purchase) {
            if ($purchase->journal_entry_id) {
                JournalEntry::where('id', $purchase->journal_entry_id)->delete();
            }
        });
    }

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'purchased_at' => 'date',
    ];

    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
