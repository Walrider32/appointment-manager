<!DOCTYPE html>
<html>
<head>
    <title>Appointments</title>
    
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.js"></script>
</head>
<body>
  
<div class="container">
    <br />
    <h1 class="text-center text-primary"><u>Appointments</u></h1>
    <br />

    <div id="calendar"></div>

</div>
   
<script>
$(document).ready(function () {
    $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
        }
    })

    var calendar = $('#calendar').fullCalendar({
        editable:true,
        header: {
            left: 'prev,today,next',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        events: {
            url: '/calendar/appointments',
            type: 'GET',
            data: {
                // Additional parameters, if needed
            },
            success: function (events) {
                console.log(events);
            },
            error: function (err) {
                console.log('Error fetching events:', err);
            }
        }
    })
});
</script>
  
</body>
</html>
