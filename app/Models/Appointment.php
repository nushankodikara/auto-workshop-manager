<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Carbon\Carbon;

#[Fillable([
    'client_id', 'vehicle_id', 'appointment_date', 'appointment_time',
    'service_type', 'estimated_duration', 'notes', 'status', 'job_card_id',
    'notified_on_create', 'notified_day_prior', 'notified_morning', 'created_by',
])]
class Appointment extends Model
{
    protected $casts = [
        'notified_on_create' => 'boolean',
        'notified_day_prior' => 'boolean',
        'notified_morning'   => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Normalise a Sri Lankan phone number for FitSMS:
     * strip spaces/dashes/+, replace leading 0 with 94.
     */
    public static function normalisePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\+]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '94' . substr($phone, 1);
        }
        return $phone;
    }

    /**
     * Human-readable status label.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'   => 'Pending',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'no-show'   => 'No-Show',
            'cancelled' => 'Cancelled',
            default     => ucfirst($this->status),
        };
    }

    /**
     * Tailwind colour classes for status badges.
     */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending'   => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/25',
            'confirmed' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/25',
            'completed' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/25',
            'no-show'   => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/25',
            'cancelled' => 'bg-slate-500/10 text-slate-500 border-slate-400/25',
            default     => 'bg-slate-200 text-slate-600 border-slate-300',
        };
    }

    // ── Custom Accessors & Mutators for SQLite Compatibility ───────

    public function getAppointmentDateAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    public function setAppointmentDateAttribute($value)
    {
        $this->attributes['appointment_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }
}
