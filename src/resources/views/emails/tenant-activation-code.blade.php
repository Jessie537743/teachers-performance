<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your activation code</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f8fafc; margin:0; padding:24px;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:32px;">
        <h1 style="margin:0 0 8px; font-size:20px; color:#0f172a;">Welcome to Teachers Performance Platform</h1>
        <p style="margin:0 0 24px; color:#475569;">
            Hi {{ $code->intended_admin_name }}, your school <strong>{{ $tenant->name }}</strong> is provisioned and ready to activate.
        </p>

        <div style="background:#f1f5f9; border-radius:6px; padding:16px; margin-bottom:24px;">
            <p style="margin:0 0 6px; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; color:#64748b;">Activation code</p>
            <p style="margin:0 0 16px; font-family:Menlo, monospace; font-size:22px; letter-spacing:2px; color:#0f172a;">{{ $code->code }}</p>

            <p style="margin:0 0 6px; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; color:#64748b;">School URL</p>
            <p style="margin:0; font-family:Menlo, monospace; color:#0f172a;">{{ $tenantUrl }}</p>
        </div>

        <p style="margin:0 0 16px; color:#475569;">Click below to set your admin password and sign in:</p>
        <p style="margin:0 0 24px;">
            <a href="{{ $activateUrl }}" style="display:inline-block; background:#0f172a; color:#ffffff; text-decoration:none; padding:10px 18px; border-radius:6px;">
                Activate my school
            </a>
        </p>

        <p style="margin:0; font-size:12px; color:#94a3b8;">
            This code expires on {{ $code->expires_at->toDayDateTimeString() }}. Plan: {{ ucfirst($code->plan) }}.
        </p>
    </div>
</body>
</html>
