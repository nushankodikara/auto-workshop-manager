<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackerExpenseLog extends Model
{
    protected $fillable = [
        'id',
        'tracker_vehicle_id',
        'odometer_km',
        'category',
        'amount',
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
