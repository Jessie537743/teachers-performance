<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f8fafc; margin:0; padding:24px;">
<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:32px;">
    <h1 style="margin:0 0 8px;font-size:20px;color:#0f172a;">Evaluation period is now open</h1>
    <p style="color:#475569;margin:0 0 16px;">
        Hi {{ $user->name }} — the evaluation period for <strong>{{ $period->school_year }} — {{ $period->semester }}</strong> is now open. You can begin submitting your evaluations.
    </p>

    <div style="background:#f1f5f9;border-radius:6px;padding:16px;margin:16px 0;">
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Period</p>
        <p style="margin:0 0 12px;color:#0f172a;">{{ $period->school_year }} — {{ $period->semester }}</p>
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Open through</p>
        <p style="margin:0;color:#0f172a;">{{ optional($period->end_date)->toFormattedDateString() ?? 'until administrator closes' }}</p>
    </div>

    <p style="margin:0 0 24px;">
        <a href="{{ $evaluateUrl }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;">
            Start evaluating
        </a>
    </p>

    <p style="margin:0;font-size:12px;color:#94a3b8;">
        Don't wait until the last day — you'll get the most accurate evaluations done while teaching is still fresh in mind.
    </p>
</div>
</body>
</html>
