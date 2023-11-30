<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CalendarController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/calendar');
});

Route::get('/calendar', [CalendarController::class, 'index']);

Route::get('/calendar/appointments', [CalendarController::class, 'getCalendarAppointments']);

Route::post('/calendar/appointment/book', [CalendarController::class, 'bookAppointment']);
