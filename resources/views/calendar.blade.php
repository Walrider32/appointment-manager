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
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js'></script>

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
            initialView: 'dayGridMonth',
            weekNumberCalculation: 'ISO',
            selectable: true,
            timeZone: 'UTC',
            //initialDate: '2023-12-01',
            headerToolbar: {
                start: 'prev,today,next',
                center: 'title',
                end: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: {
                url: '/calendar/appointments',
                type: 'GET',
                success: function (events) {
                    console.log(events);
                },
                error: function (err) {
                    console.log('Error fetching events:', err);
                },
            },
            select: function (info) {
                // Display a popup or form for entering client's name

                console.log(info.start.toISOString());
                console.log(info.end.toISOString());
                var customerName = prompt('Enter customer name:');
                if (customerName) {
                    var dayOfWeek = info.start.getDay();

                    $.ajax({
                        url: '/calendar/appointment/book',
                        type: 'POST',
                        data: {
                            start: info.start.toISOString(),
                            end: info.end.toISOString(),
                            customerName: customerName,
                            dayOfWeek: dayOfWeek,
                        },
                        success: function (response) {
                            alert('Appointment booked successfully!');
                            calendar.refetchEvents();
                        },
                        error: function (err) {
                            alert('Error reserving appointment!');
                            console.log('Error reserving appointment:', err);
                        },
                    });
                }
                calendar.unselect();
            },
        });
        calendar.render();
    });
</script>
  
</body>
</html>
