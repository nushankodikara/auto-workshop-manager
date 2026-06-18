<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'type', 'default_amount'])]
class PayrollCategory extends Model
{
    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
        ];
    }
}
