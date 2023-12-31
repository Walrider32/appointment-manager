<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start',
        'end',
        'day_of_week',
    ];

    protected $casts = [
        'day_of_week' => 'array',
    ];
}
