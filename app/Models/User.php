<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deleted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Helpers for role checks
     */
    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    public function isNurse(): bool
    {
        return $this->role === 'nurse';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isRoot(): bool
    {
        return $this->role === 'root_user';
    }

    /**
     * Boot: Prevent duplicate root_user
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($user) {
            if ($user->role === 'root_user') {
                $existingRootUser = static::where('role', 'root_user')->exists();

                if ($existingRootUser) {
                    Log::warning("Duplicate root_user creation blocked", [
                        'email' => $user->email,
                    ]);

                    throw ValidationException::withMessages([
                        'role' => ['Only one root user is allowed.'],
                    ]);
                }
            }
        });

        static::updating(function ($user) {
            if ($user->isDirty('role') && $user->role === 'root_user') {
                $existingRootUser = static::where('role', 'root_user')
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($existingRootUser) {
                    Log::warning("Changing role to root_user blocked", [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);

                    throw ValidationException::withMessages([
                        'role' => ['Only one root user is allowed.'],
                    ]);
                }
            }
        });
    }

    /**
     * Send password reset
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
