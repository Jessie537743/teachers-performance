<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f8fafc; margin:0; padding:24px;">
<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:32px;">
    <h1 style="margin:0 0 8px;font-size:20px;color:#0f172a;">Password reset request received</h1>
    <p style="color:#475569;margin:0 0 16px;">
        Hi {{ $user->name }} — we received a request to reset your password. An administrator will review and approve it shortly.
    </p>

    <div style="background:#f1f5f9;border-radius:6px;padding:16px;margin:16px 0;">
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Submitted</p>
        <p style="margin:0 0 12px;color:#0f172a;">{{ $request->created_at->toDayDateTimeString() }}</p>
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Account</p>
        <p style="margin:0;color:#0f172a;">{{ $user->email }}</p>
    </div>

    <p style="color:#475569;margin:0 0 16px;">
        You'll receive another email as soon as your request is approved (usually within 1 business day). You don't need to do anything in the meantime.
    </p>

    <p style="margin:0;font-size:12px;color:#94a3b8;">
        If you didn't request this, please contact your administrator right away — your account may be at risk.
    </p>
</div>
</body>
</html>
