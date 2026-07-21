<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Carbon\Carbon;

#[Fillable(['job_card_id', 'amount', 'payment_method', 'transaction_reference', 'notes', 'paid_at', 'created_by', 'journal_entry_id'])]
class JobCardAdvancedPayment extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($payment) {
            \App\Services\DoubleEntryService::postAdvancedPayment($payment);
        });
        
        static::deleted(function ($payment) {
            \App\Services\DoubleEntryService::postAdvancedPayment($payment);
            if ($payment->journal_entry_id) {
                JournalEntry::where('id', $payment->journal_entry_id)->delete();
            }
        });
    }

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
