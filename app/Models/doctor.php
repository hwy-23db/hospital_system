<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // <- Use Authenticatable
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Doctor extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'employee_number',
        'name',
        'nrc_number',
        'email',
        // 'phone',
        'specialization',

    ];
public function treatments()
{
    return $this->hasMany(TreatmentRecord::class);
}


}
