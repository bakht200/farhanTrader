<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ url('/login?expired=1') }}">
    <title>Session expired</title>
    <script>
        location.replace(@json(url('/login?expired=1')));
    </script>
</head>
<body style="font-family: sans-serif; padding: 2rem; text-align: center;">
    <p>Your session expired.</p>
    <p><a href="{{ url('/login?expired=1') }}">Sign in again</a></p>
</body>
</html>
