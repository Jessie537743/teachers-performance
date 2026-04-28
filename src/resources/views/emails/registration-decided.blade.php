<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f8fafc; margin:0; padding:24px;">
<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:32px;">
    @if ($req->status === 'approved')
        <h1 style="margin:0 0 8px;font-size:20px;color:#065f46;">You're approved, {{ $req->name }} 🎉</h1>
        <p style="color:#475569;margin:0 0 16px;">
            Your <strong>{{ ucfirst($req->kind) }}</strong> registration was approved on
            {{ $req->decided_at?->toDayDateTimeString() }}. You can now sign in with the email and password you submitted.
        </p>
        <p style="margin:24px 0;">
            <a href="{{ $loginUrl }}" style="display:inline-block;background:#0f172a;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;">Sign in now</a>
        </p>
    @else
        <h1 style="margin:0 0 8px;font-size:20px;color:#9f1239;">Registration update</h1>
        <p style="color:#475569;margin:0 0 16px;">
            Hi {{ $req->name }} — unfortunately your <strong>{{ ucfirst($req->kind) }}</strong> registration could not be approved at this time.
        </p>
        @if (filled($req->reason))
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:16px;margin:16px 0;">
                <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#991b1b;">Reason</p>
                <p style="margin:0;color:#7f1d1d;">{{ $req->reason }}</p>
            </div>
        @endif
        <p style="color:#475569;margin:0 0 16px;">
            If you believe this is a mistake or need clarification, contact your department head or the platform administrator. You may resubmit your registration with corrected details.
        </p>
    @endif
    <p style="margin:0;font-size:12px;color:#94a3b8;">Reference: REG-{{ str_pad((string) $req->id, 6, '0', STR_PAD_LEFT) }}</p>
</div>
</body>
</html>
