<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackerUser extends Model
{
    protected $fillable = ['id', 'phone', 'email', 'first_name', 'last_name'];
    public $incrementing = false;
    protected $keyType = 'string';
}
