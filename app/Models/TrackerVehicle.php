<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackerVehicle extends Model
{
    protected $fillable = [
        'id',
        'tracker_user_id',
        'make',
        'model',
        'year',
        'plate_number',
        'default_fuel_type',
        'current_odometer',
        'is_tdc_verified',
        'tdc_vehicle_id'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function user()
    {
        return $this->belongsTo(TrackerUser::class, 'tracker_user_id', 'id');
    }

    public function fuelLogs()
    {
        return $this->hasMany(TrackerFuelLog::class, 'tracker_vehicle_id', 'id');
    }

    public function expenseLogs()
    {
        return $this->hasMany(TrackerExpenseLog::class, 'tracker_vehicle_id', 'id');
    }

    /**
     * Calculate fuel economy in km/L using the full-to-full method.
     */
    public function getFuelEconomy()
    {
        $logs = $this->fuelLogs()
            ->whereNotNull('liters')
            ->where('liters', '>', 0)
            ->orderBy('odometer_km', 'asc')
            ->get();

        if ($logs->count() < 2) {
            return null;
        }

        $minOdo = $logs->first()->odometer_km;
        $maxOdo = $logs->last()->odometer_km;
        $dist = $maxOdo - $minOdo;

        if ($dist <= 0) {
            return null;
        }

        // Sum liters of all logs except the first one
        $totalLiters = $logs->slice(1)->sum('liters');
        if ($totalLiters <= 0) {
            return null;
        }

        return $dist / $totalLiters;
    }

    /**
     * Get the total fuel + general expenses logged.
     */
    public function getTotalExpenditure()
    {
        $fuelCost = $this->fuelLogs()->sum('total_cost') ?: 0;
        $expenseCost = $this->expenseLogs()->sum('amount') ?: 0;
        return $fuelCost + $expenseCost;
    }
}
