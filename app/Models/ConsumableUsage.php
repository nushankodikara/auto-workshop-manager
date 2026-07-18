<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['consumable_id', 'quantity_consumed', 'recorded_at', 'notes'])]
class ConsumableUsage extends Model
{
    protected $casts = [
        'quantity_consumed' => 'decimal:2',
        'recorded_at' => 'date',
    ];

    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }
}
