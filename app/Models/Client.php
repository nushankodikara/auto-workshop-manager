<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'email', 'phone', 'address', 'tracker_user_id'])]
class Client extends Model
{
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function jobCards()
    {
        return $this->hasManyThrough(JobCard::class, Vehicle::class);
    }
}
