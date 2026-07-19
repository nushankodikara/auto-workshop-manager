<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'job_card_id',
    'description',
    'amount',
    'type'
])]
class JobCardTransportation extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }
}
