<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['payroll_slip_id', 'category_name', 'type', 'amount'])]
class PayrollSlipItem extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function payrollSlip()
    {
        return $this->belongsTo(PayrollSlip::class);
    }
}
