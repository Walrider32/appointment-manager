<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date_start',
        'date_end',
        'recurring',
        'day_of_week',
        'time_start',
        'time-end',
    ];

    protected $casts = [
        'day_of_week' => 'array',
    ];
}