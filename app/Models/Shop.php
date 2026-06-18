<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'address'])]
class Shop extends Model
{
    public function jobCards()
    {
        return $this->hasMany(JobCard::class);
    }
}
