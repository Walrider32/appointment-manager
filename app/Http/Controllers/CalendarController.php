<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar');
    }

    public function getCalendarSchedules(Request $request)
    {
        // Get the start and end parameters from the AJAX request
        $start = Carbon::parse($request->input('start'))->subDay();
        $end = Carbon::parse($request->input('end'));

        // Query the database for Schedules that overlap with the requested time range
        $schedules = Schedule::where(function ($query) use ($start, $end) {
            $query->whereBetween('date_start', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('date_start', '<', $start)
                        ->where(function ($subQuery) {
                            $subQuery->where('recurring', '!=', 'none')
                                    ->orWhereNull('date_end');
                        });
                });
        })->get();

        $appointments = Appointment::where(function ($query) use ($start, $end) {
            $query->whereBetween('start', [$start, $end])
                ->orWhere(function ($q) use ($start) {
                    $q->where('start', '<', $start);
                });
        })->get();

        // Prepare an array to store the formatted events
        $events = [];

        // Process each fetched schedule
        foreach ($schedules as $schedule) {
            // Handle recurring schedules and get their occurrences
            $occurrences = $this->getScheduleOccurrences($schedule, $start, $end);
    
            // Format each occurrence and add it to the events array
            foreach ($occurrences as $occurrence) {
                $events[] = [
                    'id' => $schedule->id,
                    'start' => $occurrence['start'],
                    'end' => $occurrence['end'],
                    'display' => 'background',
                ];
            }
        }
        
        foreach ($appointments as $appointment) {
            $events[] = [
                'id' => $appointment->id,
                'title' => $appointment->name,
                'start' => $appointment->start,
                'end' => $appointment->end,
            ];
        }

        // Return the formatted events as JSON
        return response()->json($events);
    } 

    // Calculate recurring occurrences for a given appointment within a specific date range
    private function getScheduleOccurrences($schedule, $rangeStart, $rangeEnd)
    {
        $occurrences = [];
        $currentDate = Carbon::parse($schedule->date_start);

        if (!in_array($currentDate->dayOfWeek, $schedule->day_of_week)) {
            $currentDate = $this->adjustToFirstOccurrence($currentDate, $schedule->day_of_week);
        }

        // Determine the end date if the date in null
        $calculationEndDate = $schedule->date_end ? Carbon::parse($schedule->date_end) : $rangeEnd;

        while ($currentDate->lte($calculationEndDate) && $currentDate->lte($rangeEnd)) {
            if ($currentDate->between($rangeStart, $rangeEnd) && $this->isRecurringDateMatch($schedule, $currentDate)) {
                $start = Carbon::parse($currentDate->toDateString() . ' ' . $schedule->time_start);
                $end = Carbon::parse($currentDate->toDateString() . ' ' . $schedule->time_end);
                $occurrences[] = [
                    'start' => $start->toDateTimeString(),
                    'end' => $end->toDateTimeString(),
                ];
            }
            $currentDate = $this->incrementDateBasedOnRecurrence($schedule, $currentDate);
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

    private function isRecurringDateMatch($schedule, $date)
    {
        switch ($schedule->recurring) {
            case 'none':
                return $date->isSameDay(Carbon::parse($schedule->date_start));

            case 'weekly':
                return in_array($date->dayOfWeek, $schedule->day_of_week);

            case 'even_weeks':
                $weekDifference = $date->diffInWeeks(Carbon::parse($schedule->date_start));
                return in_array($date->dayOfWeek, $schedule->day_of_week) && $weekDifference % 2 == 0;

            case 'odd_weeks':
                $weekDifference = $date->diffInWeeks(Carbon::parse($schedule->date_start));
                return in_array($date->dayOfWeek, $schedule->day_of_week) && $weekDifference % 2 != 0;

            default:
                return false;
        }
    }

    private function incrementDateBasedOnRecurrence($schedule, $date)
    {
        switch ($schedule->recurring) {
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

    public function bookAppointment(Request $request)
    {
        try {
            if ($request->ajax() && $request->input('type') == 'book') {

                $cleanDateStartString = preg_replace('/\s*\(.*\)\s*/', '', $request->input('calendarStart'));
                $calendarStart = Carbon::parse($cleanDateStartString)->subDay()->format('Y-m-d H:i:s');
                $cleanDateEndString = preg_replace('/\s*\(.*\)\s*/', '', $request->input('calendarEnd'));
                $calendarEnd = Carbon::parse($cleanDateEndString)->format('Y-m-d H:i:s');

                $customerName = $request->input('customerName');
                $dayOfWeek = $request->input('dayOfWeek');
                $bookStart = Carbon::parse($request->input('bookStart'));
                $bookEnd = Carbon::parse($request->input('bookEnd'));
                $bookTimeStart = $bookStart->toTimeString();
                $bookTimeEnd = $bookEnd->toTimeString();

                // Check if the new appointment overlaps with existing appointments
                $overlappingAppointments = Appointment::where(function ($query) use ($bookStart, $bookEnd) {
                    $query->where(function ($q) use ($bookStart, $bookEnd) {
                        $q->whereBetween('start', [$bookStart, $bookEnd])
                            ->orWhereBetween('end', [$bookStart, $bookEnd])
                            ->orWhere(function ($subQuery) use ($bookStart) {
                                $subQuery->where('start', '<', $bookStart)
                                    ->where('end', '>', $bookStart);
                            });
                    });
                })->get();

                if ($overlappingAppointments->count() > 0) {
                    // If there are overlapping appointments, return an error response
                    return response()->json(['error' => 'Overlapping appointments.'], 400);
                }

                // Check if the new appointment overlaps with generated schedules
                $schedules = Schedule::where(function ($query) use ($calendarStart, $calendarEnd) {
                    $query->whereBetween('date_start', [$calendarStart, $calendarEnd])
                        ->orWhere(function ($q) use ($calendarStart, $calendarEnd) {
                            $q->where('date_start', '<', $calendarStart)
                                ->where(function ($subQuery) {
                                    $subQuery->where('recurring', '!=', 'none')
                                            ->orWhereNull('date_end');
                                });
                        });
                })->get();

                foreach ($schedules as $schedule) {
                    // Get the occurrences for the schedule
                    $occurrences = $this->getScheduleOccurrences($schedule, $calendarStart, $calendarEnd);

                    // Check if the new appointment overlaps with any occurrence
                    foreach ($occurrences as $occurrence) {
                        $occurrenceStart = Carbon::parse($occurrence['start']);
                        $occurrenceEnd = Carbon::parse($occurrence['end']);

                        $occurrenceTimeStart = Carbon::parse($occurrence['start'])->toTimeString();
                        $occurrenceTimeEnd = Carbon::parse($occurrence['end'])->toTimeString();

                        // Check if the occurrence is on the same day as the selected day
                        if ($bookStart->isSameDay($occurrenceStart)) {
                            // Check if the selected time range is entirely within the schedule times
                            if ($bookTimeStart >= $occurrenceTimeStart && $bookTimeEnd <= $occurrenceTimeEnd) {

                                $newAppointment = Appointment::create([
                                    'name' => $customerName,
                                    'start' => $bookStart->toDateTimeString(),
                                    'end' => $bookEnd->toDateTimeString(),
                                    'day_of_week' => [(int)$dayOfWeek],
                                ]);

                                return response()->json(['success' => true]);
                            }
                        }
                        // Check the next schedule
                        elseif ($bookTimeStart < $occurrenceTimeEnd && $bookTimeEnd > $occurrenceTimeStart) {
                            continue 2;
                        }
                    }
                }
                return response()->json(['error' => 'Outside of schedule time.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function cancelAppointment(Request $request) 
    {
        try {
            if ($request->ajax() && $request->input('type') == 'delete') {
                $appointmentId = $request->input('id');
                
                // Find the appointment
                $appointment = Appointment::find($appointmentId);
                if (!$appointment) {
                    return response()->json(['error' => 'Appointment not found.'], 404);
                }
    
                // Delete the appointment
                $appointment->delete();
    
                return response()->json(['success' => true]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}

