<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f8fafc; margin:0; padding:24px;">
<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:32px;">
    <h1 style="margin:0 0 8px;font-size:20px;color:#0f172a;">Thanks, {{ $req->name }} — your registration is in</h1>
    <p style="color:#475569;margin:0 0 16px;">
        We received your <strong>{{ ucfirst($req->kind) }}</strong> registration request and routed it to
        @if ($req->kind === 'student')
            your department's dean
        @else
            the platform administrator
        @endif
        for approval.
    </p>

    <div style="background:#f1f5f9;border-radius:6px;padding:16px;margin:16px 0;">
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Submitted</p>
        <p style="margin:0 0 12px;color:#0f172a;">{{ $req->created_at->toDayDateTimeString() }}</p>
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Reference</p>
        <p style="margin:0;font-family:Menlo,monospace;color:#0f172a;">REG-{{ str_pad((string) $req->id, 6, '0', STR_PAD_LEFT) }}</p>
    </div>

    <p style="color:#475569;margin:0 0 16px;">
        We'll email you again once your request has been reviewed (usually within 1-2 business days). You don't need to do anything in the meantime.
    </p>

    <p style="margin:0;font-size:12px;color:#94a3b8;">
        If you didn't make this request, please reply and let us know.
    </p>
</div>
</body>
</html>
