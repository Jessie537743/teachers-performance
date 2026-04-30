@php
    $appName = \App\Models\Setting::get('app_name', config('app.name', 'Evaluation System'));
@endphp
<div class="institution-header" style="display:flex;align-items:center;justify-content:center;gap:16px;padding:14px 20px;border-radius:10px;background:#fff;">
    <img src="{{ $appLogo }}" alt="{{ $appName }}"
         style="height:64px;width:64px;object-fit:contain;flex-shrink:0;">
    <div style="text-align:left;line-height:1.25;">
        <div style="font-size:15px;font-weight:700;color:#0f172a;letter-spacing:.01em;">{{ $appName }}</div>
        <div style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.12em;">Performance Evaluation Report</div>
    </div>
</div>
