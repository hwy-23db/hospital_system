<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Hospital Management System</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f7fa;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .email-header {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .email-header .hospital-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .email-body {
            padding: 40px 30px;
            color: #334155;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            color: #475569;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .button-container {
            text-align: center;
            margin: 35px 0;
        }

        .reset-button {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(2, 132, 199, 0.3);
            transition: all 0.3s ease;
        }

        .reset-button:hover {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            box-shadow: 0 6px 12px rgba(2, 132, 199, 0.4);
            transform: translateY(-2px);
        }

        .security-note {
            background-color: #f1f5f9;
            border-left: 4px solid #0ea5e9;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }

        .security-note h3 {
            margin: 0 0 10px 0;
            color: #1e293b;
            font-size: 16px;
            font-weight: 600;
        }

        .security-note p {
            margin: 5px 0;
            font-size: 14px;
            color: #64748b;
        }

        .alternative-link {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 4px;
            margin: 25px 0;
            word-break: break-all;
        }

        .alternative-link p {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
        }

        .alternative-link a {
            color: #0ea5e9;
            font-size: 13px;
            word-break: break-all;
        }

        .footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #64748b;
        }

        .footer .hospital-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 30px 0;
        }

        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 20px;
            }

            .email-header {
                padding: 25px 15px;
            }

            .reset-button {
                padding: 12px 30px;
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="hospital-icon">🏥</div>
            <h1>Hospital Management System</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <div class="greeting">
                Hello {{ $user->name }},
            </div>

            <div class="message">
                We received a request to reset your password for your Hospital Management System account.
                If you made this request, please click the button below to reset your password.
            </div>

            <div class="button-container">
                <a href="{{ $url }}" class="reset-button">Reset Password</a>
            </div>

            <div class="security-note">
                <h3>🔒 Security Information</h3>
                <p>• This password reset link will expire in <strong>60 minutes</strong></p>
                <p>• If you did not request a password reset, please ignore this email</p>
                <p>• For security reasons, never share this link with anyone</p>
                <p>• If you continue to have problems, please contact the IT department</p>
            </div>

            <div class="divider"></div>

            <div class="alternative-link">
                <p>If the button above doesn't work, copy and paste this link into your browser:</p>
                <a href="{{ $url }}">{{ $url }}</a>
            </div>

            <div class="message" style="margin-top: 30px; font-size: 14px; color: #64748b;">
                <strong>Note:</strong> This is an automated email. Please do not reply to this message.
                If you have any questions or concerns, please contact your system administrator.
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="hospital-name">Hospital Management System</p>
            <p>Secure Healthcare Management Platform</p>
            <p style="margin-top: 15px; font-size: 12px; color: #94a3b8;">
                © {{ date('Y') }} Hospital Management System. All rights reserved.
            </p>
            <p style="margin-top: 10px; font-size: 12px; color: #94a3b8;">
                This email was sent to {{ $user->email }}
            </p>
        </div>
    </div>
</body>

</html>
