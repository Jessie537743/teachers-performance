@php
    $pageTitle = 'Privacy Policy';
    $pageDescription = 'How we collect, use, and protect personal information on the Teachers Performance Platform.';
    $lastUpdated = 'May 1, 2026';
    $appName = \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance Platform'));
@endphp
@extends('central.legal.layout')

@section('legal')
    <p>This Privacy Policy explains how {{ $appName }} ("we", "us") handles personal information when you visit our marketing site or use the Service. We comply with the Republic Act No. 10173 (the Philippine Data Privacy Act of 2012) and align our practices with the principles of the EU GDPR for international users.</p>

    <h2>1. Roles</h2>
    <p>For data submitted to a School's tenant (faculty profiles, evaluation responses, comments, performance results), the <strong>School is the Personal Information Controller</strong> and we act as the <strong>Personal Information Processor</strong>. For account data on our marketing/checkout site, we act as the Controller.</p>

    <h2>2. Information We Collect</h2>
    <h3>2.1 You provide it</h3>
    <ul>
        <li><strong>Checkout & account info:</strong> School name, school admin name and email, billing details, plan selection.</li>
        <li><strong>Tenant content:</strong> Faculty and student profiles, departments, courses, evaluation criteria and responses, comments, signatures, uploaded files.</li>
        <li><strong>Support communications:</strong> Messages you send via the contact form or email.</li>
    </ul>
    <h3>2.2 Collected automatically</h3>
    <ul>
        <li><strong>Usage data:</strong> Pages visited, IP address, browser type, device, timestamps, audit-log events.</li>
        <li><strong>Cookies:</strong> Session cookies for authentication and CSRF protection. We do not use third-party advertising cookies.</li>
    </ul>

    <h2>3. How We Use Information</h2>
    <ul>
        <li>Provide, operate, secure, and improve the Service.</li>
        <li>Authenticate users and enforce tenant isolation.</li>
        <li>Process payments and send transactional emails (activation codes, password resets, evaluation-period notifications, registration approvals).</li>
        <li>Generate AI-driven analytics for the School that submitted the data — never across Schools.</li>
        <li>Detect, prevent, and respond to abuse, fraud, and security incidents.</li>
        <li>Comply with legal obligations.</li>
    </ul>

    <h2>4. Legal Bases (for users in the EU/EEA or UK)</h2>
    <ul>
        <li><strong>Contract:</strong> Most processing is necessary to provide the Service you've subscribed to.</li>
        <li><strong>Legitimate interests:</strong> Securing the platform, preventing abuse, improving features.</li>
        <li><strong>Consent:</strong> Where required (e.g., optional analytics or marketing emails); withdrawable at any time.</li>
        <li><strong>Legal obligation:</strong> Tax, accounting, and lawful government requests.</li>
    </ul>

    <h2>5. Sharing</h2>
    <p>We do <strong>not</strong> sell personal information. We share it only with:</p>
    <ul>
        <li><strong>Sub-processors</strong> we use to run the Service — currently: Railway (hosting and database), Resend (transactional email). They are bound by contract to handle data only on our instructions.</li>
        <li><strong>Your School</strong> — administrators, HR, and authorized roles within your School can access tenant data per their permissions.</li>
        <li><strong>Legal authorities</strong> when required by valid process, and only the minimum necessary.</li>
        <li><strong>Successors</strong> in connection with a merger, acquisition, or asset sale, with notice to you.</li>
    </ul>

    <h2>6. Data Storage and Security</h2>
    <ul>
        <li>Each School's data is isolated in a separate tenant database.</li>
        <li>Connections are encrypted in transit via HTTPS/TLS.</li>
        <li>Passwords are hashed using bcrypt; we never store plaintext passwords.</li>
        <li>Access to production systems is restricted, logged, and audited.</li>
        <li>We perform regular backups; you can request a one-time export on cancellation.</li>
    </ul>

    <h2>7. Retention</h2>
    <p>We keep tenant data while your subscription is active and for up to 30 days after cancellation, unless you request earlier deletion. Audit logs and billing records may be retained longer where required by law (typically up to 10 years for financial records under Philippine tax law).</p>

    <h2>8. Your Rights</h2>
    <p>Subject to local law, you have the right to:</p>
    <ul>
        <li>Access the personal information we hold about you;</li>
        <li>Correct inaccurate or incomplete information;</li>
        <li>Request deletion ("right to be forgotten");</li>
        <li>Object to or restrict certain processing;</li>
        <li>Receive a portable copy of your data;</li>
        <li>Withdraw consent where processing is based on consent;</li>
        <li>Lodge a complaint with the National Privacy Commission (Philippines) or your local data protection authority.</li>
    </ul>
    <p>For tenant data submitted by your School, please contact your School's administrator first — they are the Controller. For platform-level requests, contact us via the <a href="{{ route('central.contact') }}">contact page</a> and we will respond within 30 days.</p>

    <h2>9. Children's Privacy</h2>
    <p>The Service is intended for use by accredited educational institutions and their authorized end users. End users under the age of 18 (e.g., students) participate only under their School's authority. We do not knowingly collect personal information directly from children for our own purposes.</p>

    <h2>10. International Transfers</h2>
    <p>Our infrastructure may store and process data in jurisdictions outside the Philippines. We rely on contractual safeguards (including Standard Contractual Clauses where applicable) to protect data during such transfers.</p>

    <h2>11. AI Processing</h2>
    <p>Sentiment analysis and prediction features run on data within your tenant only. We do not train shared AI models on your tenant data, and we do not transmit your tenant content to third-party LLM providers without your School's explicit configuration.</p>

    <h2>12. Cookies</h2>
    <p>We use <em>strictly necessary</em> cookies only — session and CSRF tokens. No advertising or cross-site tracking cookies are set.</p>

    <h2>13. Changes</h2>
    <p>We may update this Policy from time to time. We will post the new effective date at the top of this page and, for material changes, notify School Admins via email at least 14 days in advance.</p>

    <h2>14. Contact</h2>
    <p>Privacy questions, data subject requests, or to reach our Data Protection Officer — use the <a href="{{ route('central.contact') }}">contact page</a> and tag your message "Privacy".</p>
@endsection
