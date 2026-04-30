<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f8fafc; margin:0; padding:24px;">
<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:32px;">
    <h1 style="margin:0 0 8px;font-size:20px;color:#0f172a;">Password reset request declined</h1>
    <p style="color:#475569;margin:0 0 16px;">
        Hi {{ $user->name }} — your password reset request was reviewed and declined by an administrator. Your password has not been changed.
    </p>

    @if(! empty($request->admin_notes))
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:16px;margin:16px 0;">
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#991b1b;">Reason from administrator</p>
        <p style="margin:0;color:#7f1d1d;">{{ $request->admin_notes }}</p>
    </div>
    @endif

    <div style="background:#f1f5f9;border-radius:6px;padding:16px;margin:16px 0;">
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Account</p>
        <p style="margin:0 0 12px;color:#0f172a;">{{ $user->email }}</p>
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Reviewed</p>
        <p style="margin:0;color:#0f172a;">{{ optional($request->reviewed_at)->toDayDateTimeString() ?? 'just now' }}</p>
    </div>

    <p style="color:#475569;margin:0 0 16px;">
        If you still need access, please contact your administrator directly or submit a new request with additional verification.
    </p>
</div>
</body>
</html>
