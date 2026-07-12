<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['job_card_id', 'outsourcing_company_id', 'description', 'cost_price', 'selling_price'])]
class JobCardOutsourcing extends Model
{
    protected function casts(): array
    {
        return [
            'cost_price'    => 'decimal:2',
            'selling_price' => 'decimal:2',
        ];
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function outsourcingCompany()
    {
        return $this->belongsTo(OutsourcingCompany::class);
    }
}
