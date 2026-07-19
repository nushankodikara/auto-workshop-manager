<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'quotation_number',
    'customer_name',
    'customer_address',
    'customer_phone',
    'customer_email',
    'tax',
    'discount_percent',
    'total_amount'
])]
class Quotation extends Model
{
    protected function casts(): array
    {
        return [
            'tax' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function revisions()
    {
        return $this->hasMany(QuotationRevision::class);
    }
}
