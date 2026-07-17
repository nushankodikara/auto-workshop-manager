<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['vehicle_id', 'shop_id', 'status', 'notes', 'estimated_cost', 'completed_at', 'mileage', 'card_number', 'last_email', 'last_sms'])]
class JobCard extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobCard) {
            if (empty($jobCard->card_number)) {
                $prefix = \App\Models\Setting::get('job_card_prefix', 'TDC-');
                $createdAt = $jobCard->created_at ? \Carbon\Carbon::parse($jobCard->created_at) : now();
                $dateTimePart = $createdAt->format('ymdHi');
                $searchPattern = $prefix . $dateTimePart . '%';
                
                $lastJobCard = self::where('card_number', 'like', $searchPattern)
                    ->orderBy('card_number', 'desc')
                    ->first();

                if ($lastJobCard) {
                    $lastNumStr = substr($lastJobCard->card_number, -3);
                    $nextNum = intval($lastNumStr) + 1;
                } else {
                    $nextNum = 1;
                }

                $xxx = str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                $jobCard->card_number = $prefix . $dateTimePart . $xxx;
            }
        });
    }

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

    public function outsourcingItems()
    {
        return $this->hasMany(JobCardOutsourcing::class);
    }

    public function miscParts()
    {
        return $this->hasMany(JobCardMiscPart::class);
    }

    public function assignments()
    {
        return $this->hasMany(JobCardAssignment::class);
    }

    public function advancedPayments()
    {
        return $this->hasMany(JobCardAdvancedPayment::class);
    }

    public function getWorkerActiveHours($worker)
    {
        $assignments = $this->assignments()->where('user_id', $worker->id)->get();
        $totalSeconds = 0;
        foreach ($assignments as $assignment) {
            $totalSeconds += $assignment->getActiveSeconds();
        }
        return round($totalSeconds / 3600, 2);
    }

    public function getWorkerOvertimeHours($worker)
    {
        $assignments = $this->assignments()->where('user_id', $worker->id)->get();
        $totalSeconds = 0;
        foreach ($assignments as $assignment) {
            $totalSeconds += $assignment->getOvertimeSeconds();
        }
        return round($totalSeconds / 3600, 2);
    }

    public function getOpenDurationAttribute()
    {
        $start = $this->created_at;
        $end = $this->completed_at ?: now();
        return $start->diffForHumans($end, [
            'syntax' => \Carbon\Constants\DiffOptions::DIFF_ABSOLUTE,
            'parts' => 2,
        ]);
    }

    public function getTicketSumAttribute()
    {
        if ($this->bill) {
            return (double)$this->bill->total_amount;
        }

        // Services sum
        $servicesSum = (double)$this->services()->sum('price');

        // Parts sum
        $partsSum = 0.00;
        $allocatedParts = $this->stockMovements()
            ->where('type', 'out')
            ->with(['inventory', 'purchaseBatch'])
            ->get();

        foreach ($allocatedParts as $mov) {
            $qty = abs($mov->quantity);
            $sellingPrice = 0.00;
            if ($mov->purchaseBatch) {
                $sellingPrice = floatval($mov->purchaseBatch->selling_price);
            } elseif ($mov->inventory) {
                $sellingPrice = floatval($mov->inventory->selling_price);
            }
            $partsSum += $qty * $sellingPrice;
        }

        $sum = $servicesSum + $partsSum;

        // Outsourcing (specialist services recorded on job card)
        $outsourcingSum = (double)$this->outsourcingItems()->sum('selling_price');

        // Misc parts (dealer-direct parts recorded on job card)
        $miscPartsSum = (double)$this->miscParts()->sum('selling_price');

        $sum = $servicesSum + $partsSum + $outsourcingSum + $miscPartsSum;

        if ($sum == 0 && $this->estimated_cost > 0) {
            return (double)$this->estimated_cost;
        }

        return $sum;
    }

    public function getAdvancedPaymentsSumAttribute()
    {
        return (double)$this->advancedPayments()->sum('amount');
    }
}
