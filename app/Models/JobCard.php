<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['vehicle_id', 'shop_id', 'status', 'notes', 'estimated_cost', 'completed_at', 'mileage'])]
class JobCard extends Model
{
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'estimated_cost' => 'decimal:2',
            'mileage' => 'integer',
        ];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function workers()
    {
        return $this->belongsToMany(User::class, 'job_card_worker');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function activities()
    {
        return $this->hasMany(Activity::class)->latest();
    }

    public function bill()
    {
        return $this->hasOne(Bill::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function services()
    {
        return $this->hasMany(JobCardService::class);
    }
}
