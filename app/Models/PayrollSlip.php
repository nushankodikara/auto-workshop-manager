<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'month', 'year', 'basic_salary', 'total_salary', 'allowance', 'deductions', 'company_benefits', 'net_salary', 'status', 'required_days', 'attended_days', 'overtime_hours', 'overtime_rate', 'overtime_amount', 'prorated_salary'])]
class PayrollSlip extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($slip) {
            if ($slip->status === 'paid') {
                \App\Services\DoubleEntryService::postPayrollSlipTransaction($slip);
            }
        });

        static::deleted(function ($slip) {
            JournalEntry::where('reference', 'SLIP-' . $slip->id)->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'total_salary' => 'decimal:2',
            'allowance' => 'decimal:2',
            'deductions' => 'decimal:2',
            'company_benefits' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'month' => 'integer',
            'year' => 'integer',
            'required_days' => 'integer',
            'attended_days' => 'float',
            'overtime_hours' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'prorated_salary' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PayrollSlipItem::class);
    }

    public function advances()
    {
        return $this->hasMany(EmployeeAdvance::class);
    }
}
