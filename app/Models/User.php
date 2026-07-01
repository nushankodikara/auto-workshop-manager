<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'allowed_modules', 'basic_salary', 'required_days', 'overtime_rate', 'is_archived', 'contact_number'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'allowed_modules' => 'array',
            'is_archived' => 'boolean',
        ];
    }

    // Role checks
    public function isSuperManager(): bool
    {
        return $this->role === 'super-manager';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isWorker(): bool
    {
        return $this->role === 'worker';
    }

    public function hasModuleAccess(string $module): bool
    {
        if ($this->isSuperManager()) {
            return true;
        }

        if ($this->isManager()) {
            $allowed = $this->allowed_modules ?? [];
            return in_array($module, $allowed);
        }

        return false;
    }

    // Relationships
    public function jobCards()
    {
        return $this->belongsToMany(JobCard::class, 'job_card_worker');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function payrollSlips()
    {
        return $this->hasMany(PayrollSlip::class);
    }
}
