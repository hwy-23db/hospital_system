<x-guest-layout>
    <div style="text-align: center; padding: 40px 20px;">
        <!-- Success Icon -->
        <div style="margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; margin: 0 auto; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);">
                <svg style="width: 50px; height: 50px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <!-- Success Message -->
        <h1 style="font-size: 28px; font-weight: 600; color: #1e293b; margin-bottom: 15px;">
            Password Reset Successful!
        </h1>

        <p style="font-size: 16px; color: #64748b; margin-bottom: 30px; line-height: 1.6;">
            Your password has been successfully reset. You can now log in to your account using your new password.
        </p>

        <!-- Login Button -->
        {{-- <div style="margin-top: 40px;">
            <a href="{{ route('login') }}" style="display: inline-block; padding: 14px 40px; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 6px rgba(2, 132, 199, 0.3); transition: all 0.3s ease;">
                Go to Login
            </a>
        </div> --}}

        <!-- Security Note -->
        <div style="margin-top: 40px; padding: 20px; background-color: #f1f5f9; border-left: 4px solid #0ea5e9; border-radius: 4px; text-align: left; max-width: 500px; margin-left: auto; margin-right: auto;">
            <h3 style="margin: 0 0 10px 0; color: #1e293b; font-size: 16px; font-weight: 600;">
                🔒 Security Reminder
            </h3>
            <p style="margin: 5px 0; font-size: 14px; color: #64748b; line-height: 1.6;">
                If you did not request this password reset, please contact the IT department immediately to secure your account.
            </p>
        </div>
    </div>
</x-guest-layout>

