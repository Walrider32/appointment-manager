<!DOCTYPE html>
<html>
<head>
    <title>Appointments</title>
    
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js'></script>
</head>
<body>
  
<div class="container">
    <br />
    <h1 class="text-center text-primary"><u>Appointments</u></h1>
    <br />
    <div id="calendar"></div>
</div>
   
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
            editable: true,
            //initialDate: '2023-12-01',
            headerToolbar: {
                start: 'prev,today,next',
                center: 'title',
                end: 'dayGridMonth,dayGridWeek,dayGridDay'
            },
            events: {
                url: '/calendar/appointments',
                type: 'GET',
                success: function (events) {
                    console.log(events);
                },
                error: function (err) {
                    console.log('Error fetching events:', err);
                }
            }
        });
        calendar.render();
    });
</script>
  
</body>
</html>
