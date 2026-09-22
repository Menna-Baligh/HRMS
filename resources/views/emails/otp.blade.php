<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verification Code</title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #f4f7fb;
    font-family: Arial, Helvetica, sans-serif;
">

<table width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color: #f4f7fb; padding: 40px 15px;">

    <tr>
        <td align="center">

            <table width="600" cellpadding="0" cellspacing="0" border="0"
                    style="
                        max-width: 600px;
                        width: 100%;
                        background-color: #ffffff;
                        border-radius: 12px;
                        overflow: hidden;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                    ">

                <!-- Header -->
                <tr>
                    <td style="
                        background-color: #1f2937;
                        padding: 28px 35px;
                        text-align: center;
                    ">
                        <h1 style="
                            margin: 0;
                            color: #ffffff;
                            font-size: 24px;
                            font-weight: 700;
                        ">
                            {{ config('app.name') }}
                        </h1>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding: 40px 35px;">

                        <h2 style="
                            margin: 0 0 20px;
                            color: #111827;
                            font-size: 24px;
                        ">
                            Verification Code
                        </h2>

                        <p style="
                            margin: 0 0 15px;
                            color: #4b5563;
                            font-size: 16px;
                            line-height: 1.6;
                        ">
                            Hello,
                        </p>

                        <p style="
                            margin: 0 0 30px;
                            color: #4b5563;
                            font-size: 16px;
                            line-height: 1.6;
                        ">
                            Use the verification code below to complete your verification:
                        </p>

                        <!-- OTP -->
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td align="center">

                                    <div style="
                                        display: inline-block;
                                        background-color: #f3f4f6;
                                        border: 1px solid #e5e7eb;
                                        border-radius: 10px;
                                        padding: 18px 35px;
                                        letter-spacing: 8px;
                                        font-size: 30px;
                                        font-weight: 700;
                                        color: #111827;
                                    ">
                                        {{ $otp }}
                                    </div>

                                </td>
                            </tr>
                        </table>

                        <p style="
                            margin: 30px 0 10px;
                            color: #6b7280;
                            font-size: 14px;
                            line-height: 1.6;
                            text-align: center;
                        ">
                            This code will expire shortly.
                        </p>

                        <p style="
                            margin: 0 0 25px;
                            color: #4b5563;
                            font-size: 14px;
                            line-height: 1.6;
                            text-align: center;
                        ">
                            <strong>Please do not share this code with anyone.</strong>
                        </p>

                        <p style="
                            margin: 0;
                            color: #6b7280;
                            font-size: 14px;
                            line-height: 1.6;
                        ">
                            If you did not request this code, you can safely ignore this email.
                        </p>

                        <p style="
                            margin: 30px 0 0;
                            color: #4b5563;
                            font-size: 15px;
                        ">
                            Thanks,<br>
                            <strong>{{ config('app.name') }}</strong>
                        </p>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="
                        background-color: #f9fafb;
                        padding: 20px 35px;
                        text-align: center;
                        border-top: 1px solid #e5e7eb;
                    ">
                        <p style="
                            margin: 0;
                            color: #9ca3af;
                            font-size: 12px;
                        ">
                            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>

</table>

</body>
</html>