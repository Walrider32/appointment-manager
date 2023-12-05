<!DOCTYPE html>
<html>
<head>
    <title>Appointments</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica Neue, Helvetica, sans-serif;
            font-size: 14px;
        }

        #calendar {
            max-width: 1100px;
            margin: 40px auto;
        }

        #calendar .fc-highlight {
        background-color: red;
        }
    </style>
</head>
<body>
  
<div id="calendar"></div>
   
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                }
        });
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            weekNumberCalculation: 'ISO',
            selectable: true,
            timeZone: 'UTC',
            headerToolbar: {
                start: 'prev,today,next',
                center: 'title',
                end: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            views: {
                dayGridMonth: {
                    selectable: false
                }
            },
            events: {
                url: '/calendar/schedules',
                type: 'GET',
                success: function (events) {
                },
                error: function (err) {
                    console.log('Error fetching events:', err);
                },
            },
            select: function (info) {
                var customerName = prompt('Enter customer name:');

                if (customerName) {
                    var dayOfWeek = info.start.getDay();
                    $.ajax({
                        url: '/calendar/appointment/book',
                        type: 'POST',
                        data: {
                            calendarStart: calendar.currentData.dateProfile.renderRange.start,
                            calendarEnd: calendar.currentData.dateProfile.renderRange.end,
                            bookStart: info.start.toISOString(),
                            bookEnd: info.end.toISOString(),
                            customerName: customerName,
                            dayOfWeek: dayOfWeek,
                            type: 'book',
                        },
                        success: function (response) {
                            alert('Appointment booked successfully!');
                            calendar.refetchEvents();
                        },
                        error: function (xhr, status, error) {
                            var errorMessage = "Error reserving appointment!";
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            }
                            alert(errorMessage);
                        }
                    });
                }
                calendar.unselect();
            },
            eventClick: function(info) 
            {
                if(confirm("Are you sure you want to cancel?")) {
                    $.ajax({
                        url: '/calendar/appointment/cancel',
                        type: 'POST',
                        data: {
                            id: info.event._def.publicId,
                            type: 'delete',
                        },
                        success: function(response) {
                            alert('Appointment cancelled successfully!');
                            calendar.refetchEvents();
                        },
                        error: function (xhr, status, error) {
                            var errorMessage = "Appointment not found!";
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            }
                            alert(errorMessage);
                        }
                    });
                }
            }
        });
        calendar.render();
    });
</script>
  
</body>
</html>
