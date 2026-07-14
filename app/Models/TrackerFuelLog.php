<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackerFuelLog extends Model
{
    protected $fillable = [
        'id',
        'tracker_vehicle_id',
        'odometer_km',
        'fuel_type',
        'liters',
        'price_per_liter',
        'total_cost',
        'notes',
        'logged_at'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function vehicle()
    {
        return $this->belongsTo(TrackerVehicle::class, 'tracker_vehicle_id', 'id');
    }
}
