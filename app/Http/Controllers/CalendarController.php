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
        if($request->ajax()) {
            $start = $request->input('start');
            $end = $request->input('end');

            $appointments = Appointment::where(function ($query) use ($start, $end) {
            // Include appointments that overlap with the requested time range
            $query->whereBetween('date_start', [$start, $end])
                ->orWhereBetween('date_end', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('date_start', '<=', $start)
                        ->where('date_end', '>=', $end);
            });
        })->get();

        $events = [];

        foreach ($appointments as $appointment) {
            // Handle recurring appointments
            $occurrences = $this->getAppointmentOccurrences($appointment);

            foreach ($occurrences as $occurrence) {
                $events[] = [
                    'title' => $appointment->name,
                    'start' => $occurrence['start'],
                    'end' => $occurrence['end'],
                    // Add other relevant appointment data here
                ];
            }
        }

        return response()->json($events);
        }
    }

    private function getAppointmentOccurrences($appointment)
    {
        // Logic to calculate recurring occurrences based on the appointment data
        // You can use Carbon library to manipulate dates

        $occurrences = [];

        // Example logic, customize according to your needs
        $currentDate = Carbon::parse($appointment->date_start);

        while ($currentDate->lte(Carbon::parse($appointment->date_end))) {
            // Check if the current day is in the recurring days of the week
            if (in_array($currentDate->dayOfWeek, $appointment->day_of_week)) {
                $start = Carbon::parse($currentDate->toDateString() . ' ' . $appointment->time_start);
                $end = Carbon::parse($currentDate->toDateString() . ' ' . $appointment->time_end);

                $occurrences[] = [
                    'start' => $start->toDateTimeString(),
                    'end' => $end->toDateTimeString(),
                ];
            }

            // Move to the next week for weekly recurring appointments
            $currentDate->addWeek();
        }

        return $occurrences;
    }
}
