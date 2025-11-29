<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Boot the model.
     * Prevent creating multiple root_user accounts.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($user) {
            // If trying to create a root_user, check if one already exists
            if ($user->role === 'root_user') {
                $existingRootUser = static::where('role', 'root_user')
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($existingRootUser) {
                    Log::warning('Attempt to create duplicate root_user blocked', [
                        'email' => $user->email,
                    ]);

                    throw ValidationException::withMessages([
                        'role' => ['Root user already exists. Only one root user is allowed in the system.'],
                    ]);
                }
            }
        });

        static::updating(function ($user) {
            // Prevent changing a non-root user to root_user if one already exists
            if ($user->isDirty('role') && $user->role === 'root_user') {
                $existingRootUser = static::where('role', 'root_user')
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($existingRootUser) {
                    Log::warning('Attempt to change role to root_user blocked - root user already exists', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);

                    throw ValidationException::withMessages([
                        'role' => ['Root user already exists. Only one root user is allowed in the system.'],
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
