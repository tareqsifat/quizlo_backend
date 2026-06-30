<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Quizlo Password</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 550px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #eef2f5;
        }
        .header {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 40px 30px;
            color: #334155;
            line-height: 1.6;
        }
        .content p {
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 16px;
        }
        .otp-container {
            background-color: #fdf2f2;
            border: 2px dashed #fca5a5;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin-bottom: 30px;
        }
        .otp-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #991b1b;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 36px;
            font-weight: 800;
            color: #dc2626;
            letter-spacing: 6px;
            margin: 0;
        }
        .btn-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background-color: #dc2626;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(220, 38, 38, 0.2);
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #b91c1c;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 30px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
        }
        .footer a {
            color: #dc2626;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reset Your Password</h1>
        </div>
        <div class="content">
            <p>Hi there,</p>
            <p>We received a request to reset the password for your Quizlo account. Please use the 6-digit OTP code below to reset your password, or click the reset button directly.</p>
            
            <div class="otp-container">
                <div class="otp-label">Your Password Reset Code</div>
                <div class="otp-code">{{ $otpCode }}</div>
            </div>

            <div class="btn-container">
                <a href="{{ $resetLink }}" class="btn">Reset Password Directly</a>
            </div>

            <p style="font-size: 14px; color: #64748b; margin-bottom: 0;">If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Quizlo. All rights reserved.<br>
            If you're having trouble clicking the button, copy and paste this URL into your web browser:<br>
            <a href="{{ $resetLink }}">{{ $resetLink }}</a>
        </div>
    </div>
</body>
</html>
