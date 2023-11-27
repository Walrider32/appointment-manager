<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    public function index()
    {
        $appointments = Appointment::all();

        return view('calendar', compact('appointments'));
    }

    public function getCalendarAppointments(Request $request)
    {
    // Get the start and end parameters from the AJAX request
    $start = Carbon::parse($request->input('start'));
    $end = Carbon::parse($request->input('end'));

    // Query the database for appointments that overlap with the requested time range
    $appointments = Appointment::where(function ($query) use ($start, $end) {
        $query->whereBetween('date_start', [$start, $end])
              ->orWhere(function ($q) use ($start, $end) {
                  $q->where('date_start', '<', $start)
                    ->where(function ($subQuery) {
                        $subQuery->where('recurring', '!=', 'none')
                                 ->orWhereNull('date_end');
                    });
              });
    })->get();

    // Prepare an array to store the formatted events
    $events = [];

    // Process each fetched appointment
    foreach ($appointments as $appointment) {
        // Handle recurring appointments and get their occurrences
        $occurrences = $this->getAppointmentOccurrences($appointment, $start, $end);
        
        // Format each occurrence and add it to the events array
        foreach ($occurrences as $occurrence) {
            $events[] = [
                'title' => $appointment->name,
                'start' => $occurrence['start'],
                'end' => $occurrence['end'],
            ];
        }
    }

    // Return the formatted events as JSON
    return response()->json($events);
    }

    // Calculate recurring occurrences for a given appointment within a specific date range
    private function getAppointmentOccurrences($appointment, $rangeStart, $rangeEnd)
    {
        $occurrences = [];
        $currentDate = Carbon::parse($appointment->date_start);

        if (!in_array($currentDate->dayOfWeek, $appointment->day_of_week)) {
            $currentDate = $this->adjustToFirstOccurrence($currentDate, $appointment->day_of_week);
        }

        // Determine the end date if the date in null
        $calculationEndDate = $appointment->date_end ? Carbon::parse($appointment->date_end) : $rangeEnd->copy();

        while ($currentDate->lte($calculationEndDate) && $currentDate->lte($rangeEnd)) {
            if ($currentDate->between($rangeStart, $rangeEnd) && $this->isRecurringDateMatch($appointment, $currentDate)) {
                $start = Carbon::parse($currentDate->toDateString() . ' ' . $appointment->time_start);
                $end = Carbon::parse($currentDate->toDateString() . ' ' . $appointment->time_end);
                $occurrences[] = [
                    'start' => $start->toDateTimeString(),
                    'end' => $end->toDateTimeString(),
                ];
            }
            $currentDate = $this->incrementDateBasedOnRecurrence($appointment, $currentDate);
        }

        return $occurrences;
    }

    private function adjustToFirstOccurrence($date, $daysOfWeek)
    {
        while (!in_array($date->dayOfWeek, $daysOfWeek)) {
            $date->addDay();
        }
        return $date;
    }

    private function isRecurringDateMatch($appointment, $date)
    {
        switch ($appointment->recurring) {
            case 'none':
                return $date->isSameDay(Carbon::parse($appointment->date_start));

            case 'weekly':
                return in_array($date->dayOfWeek, $appointment->day_of_week);

            case 'even_weeks':
                $weekDifference = $date->diffInWeeks(Carbon::parse($appointment->date_start));
                return in_array($date->dayOfWeek, $appointment->day_of_week) && $weekDifference % 2 == 0;

            case 'odd_weeks':
                $weekDifference = $date->diffInWeeks(Carbon::parse($appointment->date_start));
                return in_array($date->dayOfWeek, $appointment->day_of_week) && $weekDifference % 2 != 0;

            default:
                return false;
        }
    }

    private function incrementDateBasedOnRecurrence($appointment, $date)
    {
        switch ($appointment->recurring) {
            case 'none':
                return $date->addCentury(); // Effectively stops the loop

            case 'weekly':
            case 'even_weeks':
            case 'odd_weeks':
                return $date->addWeek();

            default:
                return $date->addDay(); // Fallback, should not generally reach here
        }
    }
}

