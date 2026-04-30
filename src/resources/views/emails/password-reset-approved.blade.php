<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f8fafc; margin:0; padding:24px;">
<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:32px;">
    <h1 style="margin:0 0 8px;font-size:20px;color:#0f172a;">Your password has been reset</h1>
    <p style="color:#475569;margin:0 0 16px;">
        Hi {{ $user->name }} — your password reset request has been approved. You can now sign in with the new password you submitted.
    </p>

    <p style="margin:0 0 24px;">
        <a href="{{ $loginUrl }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;">
            Sign in
        </a>
    </p>

    <div style="background:#f1f5f9;border-radius:6px;padding:16px;margin:16px 0;">
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Account</p>
        <p style="margin:0 0 12px;color:#0f172a;">{{ $user->email }}</p>
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Approved</p>
        <p style="margin:0;color:#0f172a;">{{ optional($request->reviewed_at)->toDayDateTimeString() ?? 'just now' }}</p>
    </div>

    <p style="margin:0;font-size:12px;color:#94a3b8;">
        If you did not request this reset, contact your administrator immediately — your account may have been accessed without your permission.
    </p>
</div>
</body>
</html>
