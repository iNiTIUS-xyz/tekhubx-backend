<!doctype html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <title>Password Reset</title>
    <style>
        a:hover { text-decoration: underline !important; }
    </style>
</head>
<body style="margin:0; background-color:#f2f3f8;">
    <table width="100%" bgcolor="#f2f3f8" style="font-family:'Open Sans', sans-serif;">
        <tr><td style="height:80px;">&nbsp;</td></tr>
        <tr>
            <td align="center">
                <a href="https://tekhubx.com/" target="_blank">
                    <img src="{{ asset('storage/default/logo.png') }}" alt="logo">
                </a>
            </td>
        </tr>
        <tr><td style="height:20px;">&nbsp;</td></tr>
        <tr>
            <td>
                <table align="center" width="95%" style="max-width:670px;background:#fff;border-radius:3px;text-align:center;box-shadow:0 6px 18px rgba(0,0,0,.06);">
                    <tr><td style="height:40px;">&nbsp;</td></tr>
                    <tr>
                        <td style="padding:0 35px;">
                            <h2 style="color:#1e1e2d;">Password Reset Request</h2>
                            <p style="color:#455056; font-size:15px;line-height:24px;">
                                We received a request to reset the password for your account.<br>
                                Click the button below to reset it:
                            </p>
                            <a href="{{ $resetUrl }}"
                               style="background:#20e277;color:#fff;text-decoration:none;font-weight:500;margin-top:20px;padding:10px 24px;display:inline-block;border-radius:50px;">
                                Reset Password
                            </a>
                            <p style="margin-top:20px;font-size:13px;color:#777;">
                                If you didn’t request this, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                    <tr><td style="height:40px;">&nbsp;</td></tr>
                </table>
            </td>
        </tr>
        <tr><td style="text-align:center;font-size:14px;color:#999;">&copy; www.tekhubx.com</td></tr>
        <tr><td style="height:80px;">&nbsp;</td></tr>
    </table>
</body>
</html>
