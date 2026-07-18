<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'sku', 'unit', 'description', 'quantity'])]
class Consumable extends Model
{
    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function purchases()
    {
        return $this->hasMany(ConsumablePurchase::class)->orderBy('purchased_at', 'desc');
    }

    public function usages()
    {
        return $this->hasMany(ConsumableUsage::class)->orderBy('recorded_at', 'desc');
    }
}
