<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['client_id', 'make', 'model', 'year', 'plate_number', 'vin', 'mileage'])]
class Vehicle extends Model
{
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function jobCards()
    {
        return $this->hasMany(JobCard::class);
    }
}
