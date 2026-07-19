<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id',
    'amount',
    'advance_date',
    'reason',
    'status',
    'payroll_slip_id'
])]
class EmployeeAdvance extends Model
{
    protected function casts(): array
    {
        return [
            'advance_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payrollSlip()
    {
        return $this->belongsTo(PayrollSlip::class);
    }
}
