<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class JobCardAssignment extends Model
{
    protected $fillable = ['job_card_id', 'user_id', 'assigned_at', 'unassigned_at'];

    protected $casts = [
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate active working seconds (regular hours) within 08:30 - 18:00.
     */
    public function getActiveSeconds(): int
    {
        $start = $this->assigned_at;
        $end = $this->unassigned_at ?: now();

        if ($start >= $end) {
            return 0;
        }

        $totalSeconds = 0;
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->startOfDay();

        for ($date = $startDate; $date <= $endDate; $date->addDay()) {
            $workStart = $date->copy()->setTime(8, 30, 0);
            $workEnd = $date->copy()->setTime(18, 0, 0);

            $intersectStart = $start->max($workStart);
            $intersectEnd = $end->min($workEnd);

            if ($intersectStart < $intersectEnd) {
                $totalSeconds += $intersectStart->diffInSeconds($intersectEnd);
            }
        }

        return $totalSeconds;
    }

    /**
     * Calculate overtime seconds within 18:00 - 19:00.
     */
    public function getOvertimeSeconds(): int
    {
        $start = $this->assigned_at;
        $end = $this->unassigned_at ?: now();

        if ($start >= $end) {
            return 0;
        }

        $totalSeconds = 0;
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->startOfDay();

        for ($date = $startDate; $date <= $endDate; $date->addDay()) {
            $otStart = $date->copy()->setTime(18, 0, 0);
            $otEnd = $date->copy()->setTime(19, 0, 0);

            $intersectStart = $start->max($otStart);
            $intersectEnd = $end->min($otEnd);

            if ($intersectStart < $intersectEnd) {
                $totalSeconds += $intersectStart->diffInSeconds($intersectEnd);
            }
        }

        return $totalSeconds;
    }

    /**
     * Get regular active hours as decimal.
     */
    public function getActiveHoursAttribute(): float
    {
        return round($this->getActiveSeconds() / 3600, 2);
    }

    /**
     * Get overtime hours as decimal.
     */
    public function getOvertimeHoursAttribute(): float
    {
        return round($this->getOvertimeSeconds() / 3600, 2);
    }
}
