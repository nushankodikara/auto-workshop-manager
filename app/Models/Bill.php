<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['job_card_id', 'bill_number', 'tax', 'total_amount', 'status'])]
class Bill extends Model
{
    protected function casts(): array
    {
        return [
            'tax' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function items()
    {
        return $this->hasMany(BillItem::class);
    }
}
