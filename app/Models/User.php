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
     * Relationships to role-specific tables
     */
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function nurse()
    {
        return $this->hasOne(Nurse::class);
    }

    /**
     * Boot the model.
     * Prevent creating multiple root_user accounts.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($user) {
            if ($user->role === 'root_user') {
                $existingRootUser = static::where('role', 'root_user')
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($existingRootUser) {
                    Log::warning('Attempt to create duplicate root_user blocked', [
                        'email' => $user->email,
                    ]);

                    throw ValidationException::withMessages([
                        'role' => ['Root user already exists. Only one root user is allowed.'],
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
                    Log::warning('Attempt to change role to root_user blocked', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);

                    throw ValidationException::withMessages([
                        'role' => ['Root user already exists. Only one root user is allowed.'],
                    ]);
                }
            }
        });
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
