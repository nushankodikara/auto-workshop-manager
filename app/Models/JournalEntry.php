<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['entry_date', 'reference', 'description'])]
class JournalEntry extends Model
{
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    public function items()
    {
        return $this->hasMany(JournalItem::class);
    }
}
