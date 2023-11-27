<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Appointment;

class AppointmentsTableSeeder extends Seeder
{
    public function run(): void
    {
        Appointment::create([
            'name' => 'Reserve',
            'date_start' => '2023-09-08',
            'date_end' => '2023-09-08',
            'recurring' => 'none',
            'day_of_week' => [5],
            'time_start' => '08:00:00',
            'time_end' => '10:00:00'
        ]);

        Appointment::create([
            'name' => 'Reserve',
            'date_start' => '2023-01-01',
            'date_end' => null,
            'recurring' => 'even_weeks',
            'day_of_week' => [1],
            'time_start' => '10:00:00',
            'time_end' => '12:00:00'
        ]);

        Appointment::create([
            'name' => 'Reserve',
            'date_start' => '2023-01-01',
            'date_end' => null,
            'recurring' => 'odd_weeks',
            'day_of_week' => [3],
            'time_start' => '12:00:00',
            'time_end' => '16:00:00'
        ]);

        Appointment::create([
            'name' => 'Reserve',
            'date_start' => '2023-01-01',
            'date_end' => null,
            'recurring' => 'weekly',
            'day_of_week' => [5],
            'time_start' => '10:00:00',
            'time_end' => '16:00:00'
        ]);

        Appointment::create([
            'name' => 'Reserve',
            'date_start' => '2023-06-01',
            'date_end' => '2023-11-30',
            'recurring' => 'weekly',
            'day_of_week' => [4],
            'time_start' => '16:00:00',
            'time_end' => '20:00:00'
        ]);
    }
}
