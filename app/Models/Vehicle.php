<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['client_id', 'make', 'model', 'year', 'plate_number', 'vin', 'mileage'])]
class Vehicle extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($vehicle) {
            $vehicle->jobCards->each(function ($jobCard) {
                $jobCard->delete();
            });
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function jobCards()
    {
        return $this->hasMany(JobCard::class);
    }
}
