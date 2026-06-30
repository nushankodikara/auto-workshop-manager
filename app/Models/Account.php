<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['code', 'name', 'type', 'description'])]
class Account extends Model
{
    public function journalItems()
    {
        return $this->hasMany(JournalItem::class);
    }

    /**
     * Get the net balance for this account based on normal accounting rules:
     * - Assets & Expenses normal balance is Debit (Debit - Credit).
     * - Liabilities, Equity, & Revenue normal balance is Credit (Credit - Debit).
     */
    public function getBalanceAttribute()
    {
        $debits = $this->journalItems()->sum('debit');
        $credits = $this->journalItems()->sum('credit');

        if ($this->type === 'asset' || $this->type === 'expense') {
            return $debits - $credits;
        }

        return $credits - $debits;
    }
}
