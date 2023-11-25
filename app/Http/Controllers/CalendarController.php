<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use Carbon\Carbon;

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
        $start = $request->input('start');
        $end = $request->input('end');

        // Query the database for appointments that overlap with the requested time range
        $appointments = Appointment::where(function ($query) use ($start, $end) {
            $query->whereBetween('date_start', [$start, $end])
                ->orWhereBetween('date_end', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('date_start', '<=', $start)
                        ->where('date_end', '>=', $end);
                });
        })->get();

        // Prepare an array to store the formatted events
        $events = [];

        // Process each fetched appointment
        foreach ($appointments as $appointment) {
            // Handle recurring appointments and get their occurrences
            $occurrences = $this->getAppointmentOccurrences($appointment);

            // Format each occurrence and add it to the events array
            foreach ($occurrences as $occurrence) {
                $events[] = [
                    'title' => $appointment->name,
                    'start' => $occurrence['start'],
                    'end' => $occurrence['end'],
                    'daysOfWeek' => $appointment['day_of_week'],
                    // Add other relevant appointment data here
                ];
            }
        }
        // Return the formatted events as JSON
        return response()->json($events);
    }

    // Calculate recurring occurrences for a given appointment
    private function getAppointmentOccurrences($appointment)
    {
    // Initialize an array to store occurrences
    $occurrences = [];

    // Use Carbon to manipulate dates
    $currentDate = Carbon::parse($appointment->date_start);

    // Set the end date based on the appointment's recurrence type
    $endDate = $appointment->date_end ? Carbon::parse($appointment->date_end) : null;

    // Loop until the end date is reached (or a reasonable limit to avoid infinite loops)
    $loopCount = 0;
    $maxLoops = 1000; // Adjust as needed to prevent infinite loops
    while (!$endDate || $currentDate->lte($endDate)) {
        // Check if the current day is in the recurring days of the week
        if (
            ($appointment->recurring === 'none' && $currentDate->isSameDay(Carbon::parse($appointment->date_start))) ||
            ($appointment->recurring === 'weekly' && in_array($currentDate->dayOfWeek, $appointment->day_of_week)) ||
            ($appointment->recurring === 'even_weeks' && $currentDate->week % 2 === 0 && in_array($currentDate->dayOfWeek, $appointment->day_of_week)) ||
            ($appointment->recurring === 'odd_weeks' && $currentDate->week % 2 !== 0 && in_array($currentDate->dayOfWeek, $appointment->day_of_week))
        ) {
            // Calculate start and end times for the occurrence
            $start = Carbon::parse($currentDate->toDateString() . ' ' . $appointment->time_start);
            $end = Carbon::parse($currentDate->toDateString() . ' ' . $appointment->time_end);

            // Add the occurrence to the array
            $occurrences[] = [
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
            ];
        }

        // Move to the next week for weekly recurring appointments
        $currentDate->addWeek();
        $loopCount++;

        // Break the loop if it runs for too long to avoid infinite loops
        if ($loopCount >= $maxLoops) {
            break;
        }
    }

    // Return the array of occurrences
    return $occurrences;
    }
}
