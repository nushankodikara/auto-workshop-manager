<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['job_card_id', 'name', 'price', 'description'])]
class JobCardService extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }
}
