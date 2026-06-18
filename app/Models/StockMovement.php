<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['inventory_id', 'job_card_id', 'type', 'quantity', 'notes'])]
class StockMovement extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }
}
