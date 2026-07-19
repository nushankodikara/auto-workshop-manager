<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'quotation_id',
    'revision_number',
    'revised_by',
    'reason',
    'total_amount',
    'metadata'
])]
class QuotationRevision extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'total_amount' => 'decimal:2',
        ];
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'revised_by');
    }
}
