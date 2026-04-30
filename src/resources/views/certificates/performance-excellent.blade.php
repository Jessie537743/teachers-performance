<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Performance — {{ $fullName }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Georgia", "Times New Roman", serif;
            background: #e8ecf1;
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
        .btn-print {
            background: #4f46e5;
            color: #fff;
        }
        .btn-print:hover { background: #4338ca; }
        .btn-close {
            background: #e2e8f0;
            color: #334155;
        }
        .cert-wrap {
            width: 100%;
            max-width: 900px;
            background: #fffef8;
            border: 8px double #b45309;
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
            padding: 2.75rem 3rem 3rem;
            position: relative;
        }
        .cert-inner-border {
            border: 2px solid #ca8a04;
            padding: 2.5rem 2rem;
            min-height: 420px;
            text-align: center;
            background: linear-gradient(180deg, rgba(255,251,235,0.5) 0%, rgba(255,255,255,0) 40%);
        }
        .report-header {
            width: 100%;
            margin: 0 auto 1.25rem;
            background: #fffef8;
        }
        .report-header img {
            width: 100%;
            height: auto;
            display: block;
        }
        h1 {
            font-size: 2rem;
            font-weight: 400;
            letter-spacing: 0.06em;
            margin: 0 0 1.75rem;
            color: #78350f;
            text-transform: uppercase;
        }
        .presented {
            font-size: 1rem;
            color: #57534e;
            margin-bottom: 0.35rem;
        }
        .name {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0.75rem 0 1.25rem;
            line-height: 1.2;
            border-bottom: 2px solid #d97706;
            display: inline-block;
            padding-bottom: 0.35rem;
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
            line-height: 1.7;
            color: #44403c;
            max-width: 36rem;
            margin: 0 auto 1.5rem;
        }
        .gwa-box {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.85rem 2rem;
            background: #fffbeb;
            border: 2px solid #f59e0b;
            border-radius: 0.75rem;
            font-family: system-ui, sans-serif;
        }
        .gwa-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #b45309;
            font-weight: 700;
        }
        .gwa-value {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
        }
        .meta-row {
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #57534e;
            font-family: system-ui, sans-serif;
        }
        .level-pill {
            display: inline-block;
            margin-top: 0.75rem;
            padding: 0.35rem 1rem;
            background: #dcfce7;
            color: #166534;
            font-weight: 700;
            font-size: 0.8rem;
            border-radius: 999px;
            font-family: system-ui, sans-serif;
        }
        .footer-date {
            margin-top: 2rem;
            font-size: 0.95rem;
            color: #78716c;
        }
        .signature-block {
            margin-top: 2.75rem;
            text-align: center;
            font-family: system-ui, sans-serif;
        }
        .signature-line {
            width: 260px;
            max-width: 90%;
            margin: 0 auto 0.4rem;
            border-bottom: 1px solid #475569;
        }
        .signature-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
        }
        .signature-title {
            font-size: 0.82rem;
            color: #475569;
            margin-top: 0.15rem;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .cert-wrap { box-shadow: none; border-width: 6px; max-width: none; }
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
            <div class="report-header">
                <img src="{{ asset('images/report-header.png') }}" alt="Institution Report Header">
            </div>
            <h1>Certificate of Performance</h1>
            <p class="presented">This certificate is presented to</p>
            <div class="name">{{ $fullName }}</div>
            <p class="dept"><strong>Department:</strong> {{ $department }}</p>
            <p class="achievement">
                For achieving <strong>{{ $performanceLevel }}</strong> overall performance
                for <strong>{{ $schoolYear }}</strong>, <strong>{{ $semester === 'Summer' ? 'Summer' : $semester.' Semester' }}</strong>,
                as reflected in the faculty performance evaluation (General Weighted Average).
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
