<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'phone', 'email', 'address'])]
class OutsourcingCompany extends Model
{
    public function billItems()
    {
        return $this->hasMany(BillItem::class);
    }
}
