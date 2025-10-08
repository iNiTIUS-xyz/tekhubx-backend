<!-- resources/views/emails/welcome_manager.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Manager</title>
</head>

<body>
    <h1>Welcome to the Management Team, {{ $clientManager->name }}!</h1>
    <p>We are excited to have you on board. Please click the link below to set up your password and start managing your
        account:</p>
    <p>
        <a href="{{ $setupPasswordUrl }}">Set Up Your Password</a>
    </p>
    <p>Once your password is set, you can log in and start using the system.</p>
    <p>Thank you!</p>
</body>

</html>
