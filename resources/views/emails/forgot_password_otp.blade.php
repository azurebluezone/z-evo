<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password reset code</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2430;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:8px;padding:28px;">
                    <tr>
                        <td>
                            <p style="margin:0 0 12px;font-size:16px;">Dear {{ $customerName }},</p>
                            <p style="margin:0 0 16px;font-size:14px;line-height:22px;">
                                We received a request to reset your {{ $siteName }} password.
                                Use the one-time code below to choose a new password.
                            </p>
                            <p style="margin:0 0 16px;font-size:30px;font-weight:bold;letter-spacing:8px;color:#0f62fe;">
                                {{ $otp }}
                            </p>
                            <p style="margin:0 0 16px;font-size:14px;line-height:22px;">
                                The code expires as soon as it is used. If you did not ask for a
                                password reset, you can ignore this email; your current password
                                stays the same.
                            </p>
                            <p style="margin:0;font-size:14px;">Thank you for using {{ $siteName }}.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
