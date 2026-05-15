<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Performer Certificate — {{ $fullName }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Georgia", "Times New Roman", serif;
            background: #f1f0e8;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
        }
        .toolbar {
            font-family: system-ui, -apple-system, sans-serif;
            margin-bottom: 1rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .toolbar button, .toolbar a {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-print { background: #b45309; color: #fff; }
        .btn-print:hover { background: #92400e; }
        .btn-close { background: #e2e8f0; color: #334155; }

        .cert-wrap {
            width: 100%;
            max-width: 940px;
            background: #fffdf5;
            border: 10px double #92400e;
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
            padding: 2.5rem 3rem 3rem;
            position: relative;
        }
        .cert-inner-border {
            border: 2px solid #d4a017;
            padding: 2.5rem 2rem;
            min-height: 460px;
            text-align: center;
            background:
                radial-gradient(circle at top right, rgba(212,160,23,0.07), transparent 40%),
                radial-gradient(circle at bottom left,  rgba(212,160,23,0.07), transparent 40%),
                #fffdf5;
            position: relative;
        }
        .corner-flourish {
            position: absolute;
            width: 70px;
            height: 70px;
            border: 2px solid #d4a017;
            opacity: 0.6;
        }
        .corner-flourish.tl { top: 10px; left: 10px; border-right: none; border-bottom: none; }
        .corner-flourish.tr { top: 10px; right: 10px; border-left: none; border-bottom: none; }
        .corner-flourish.bl { bottom: 10px; left: 10px; border-right: none; border-top: none; }
        .corner-flourish.br { bottom: 10px; right: 10px; border-left: none; border-top: none; }

        .report-header {
            width: 100%;
            margin: 0 auto 1.25rem;
        }
        .ribbon {
            display: inline-block;
            background: linear-gradient(180deg, #d4a017 0%, #b45309 100%);
            color: #fffdf5;
            font-family: system-ui, sans-serif;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            padding: 0.35rem 1.25rem;
            border-radius: 999px;
            margin-bottom: 0.85rem;
            box-shadow: 0 2px 6px rgba(146,64,14,0.25);
        }
        h1 {
            font-size: 2.15rem;
            font-weight: 400;
            letter-spacing: 0.07em;
            margin: 0 0 0.5rem;
            color: #78350f;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 1rem;
            color: #92400e;
            font-style: italic;
            margin: 0 0 1.75rem;
            letter-spacing: 0.05em;
        }
        .presented {
            font-size: 1rem;
            color: #57534e;
            margin-bottom: 0.35rem;
        }
        .name {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0.75rem 0 1.25rem;
            line-height: 1.2;
            border-bottom: 2px solid #d4a017;
            display: inline-block;
            padding-bottom: 0.4rem;
            min-width: 60%;
        }
        .dept {
            font-size: 1.1rem;
            color: #44403c;
            margin-bottom: 1.5rem;
            font-family: system-ui, sans-serif;
        }
        .dept strong { color: #1c1917; }
        .achievement {
            font-size: 1rem;
            line-height: 1.75;
            color: #44403c;
            max-width: 38rem;
            margin: 0 auto 1.5rem;
        }
        .gwa-box {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.85rem 2rem;
            background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
            border: 2px solid #d4a017;
            border-radius: 0.85rem;
            font-family: system-ui, sans-serif;
        }
        .gwa-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #92400e;
            font-weight: 800;
        }
        .gwa-value {
            font-size: 2.1rem;
            font-weight: 800;
            color: #0f172a;
        }
        .level-pill {
            display: inline-block;
            margin-top: 0.85rem;
            padding: 0.4rem 1.1rem;
            background: #fef3c7;
            color: #78350f;
            font-weight: 800;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            border-radius: 999px;
            font-family: system-ui, sans-serif;
            border: 1px solid #d4a017;
        }
        .meta-row {
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #57534e;
            font-family: system-ui, sans-serif;
        }
        .footer-date {
            margin-top: 1.75rem;
            font-size: 0.95rem;
            color: #78716c;
        }
        .signature-block {
            margin-top: 2.5rem;
            text-align: center;
            font-family: system-ui, sans-serif;
        }
        .signature-line {
            width: 280px;
            max-width: 90%;
            margin: 0 auto 0.4rem;
            border-bottom: 1px solid #475569;
        }
        .signature-name { font-size: 0.95rem; font-weight: 700; color: #1e293b; }
        .signature-title { font-size: 0.82rem; color: #475569; margin-top: 0.15rem; }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .cert-wrap { box-shadow: none; border-width: 8px; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn-print" onclick="window.print()">Print / Save as PDF</button>
        <a href="javascript:window.close()" class="btn-close">Close window</a>
    </div>

    <div class="cert-wrap">
        <div class="cert-inner-border">
            <span class="corner-flourish tl"></span>
            <span class="corner-flourish tr"></span>
            <span class="corner-flourish bl"></span>
            <span class="corner-flourish br"></span>

            <div class="report-header" style="display:flex;flex-direction:column;align-items:center;gap:8px;">
                <img src="{{ $appLogo }}" alt="{{ $institutionName }}"
                     style="height:80px;width:80px;object-fit:contain;">
                <div style="font-size:13px;font-weight:700;color:#0f172a;letter-spacing:.05em;text-transform:uppercase;">
                    {{ $institutionName }}
                </div>
            </div>

            <div class="ribbon">Top Performer of the Department</div>
            <h1>Certificate of Excellence</h1>
            <p class="subtitle">Awarded for being ranked #1 in the department</p>

            <p class="presented">This certificate is proudly presented to</p>
            <div class="name">{{ $fullName }}</div>
            <p class="dept"><strong>Department:</strong> {{ $department }}</p>

            <p class="achievement">
                In recognition of being the <strong>highest-ranking faculty member</strong> in
                <strong>{{ $department }}</strong> for <strong>{{ $schoolYear }}</strong>,
                <strong>{{ $semester === 'Summer' ? 'Summer' : $semester.' Semester' }}</strong>,
                as measured by the institutional faculty performance evaluation
                (General Weighted Average across student, dean, self, and peer assessments).
            </p>

            <div class="gwa-box">
                <div class="gwa-label">General Weighted Average (GWA)</div>
                <div class="gwa-value">{{ number_format($gwa, 2) }}</div>
            </div>
            <div class="level-pill">{{ $performanceLevel }}</div>

            <div class="meta-row">
                Evaluation period: {{ $schoolYear }} — {{ $semester === 'Summer' ? 'Summer' : $semester }}
            </div>
            <p class="footer-date">Awarded this {{ $awardedDate }}</p>

            @php $hrSignatory = \App\Models\Signature::activeSignatory(); @endphp
            <div class="signature-block">
                @if($hrSignatory && $hrSignatory->signature_path)
                    <img src="{{ asset('storage/' . $hrSignatory->signature_path) }}" alt="Signature"
                         style="max-height:64px;max-width:240px;object-fit:contain;display:block;margin:0 auto 4px;">
                @endif
                <div class="signature-line"></div>
                <div class="signature-name">{{ $hrSignatory?->user?->name ?: 'Pending HR signatory' }}</div>
                <div class="signature-title">{{ $hrSignatory?->title ?: 'Head, HRMDO' }}</div>
            </div>
        </div>
    </div>
</body>
</html>
