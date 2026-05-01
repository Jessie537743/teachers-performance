@php
    $pageTitle = 'Data Processing Addendum';
    $pageDescription = 'How the Teachers Performance Platform processes personal data on behalf of subscribing schools.';
    $lastUpdated = 'May 1, 2026';
    $appName = \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance Platform'));
@endphp
@extends('central.legal.layout')

@section('legal')
    <p>This Data Processing Addendum ("DPA") forms part of the <a href="{{ route('central.terms') }}">Terms of Service</a> between {{ $appName }} ("Processor") and the subscribing school ("Controller", "you"). It describes how the Processor handles Personal Data on the Controller's behalf in connection with the Service.</p>

    <h2>1. Definitions</h2>
    <ul>
        <li><strong>Personal Data</strong> — any information relating to an identified or identifiable natural person processed under the Service.</li>
        <li><strong>Data Subjects</strong> — typically faculty, students, deans, supervisors, HR, and other school staff or stakeholders enrolled in the Controller's tenant.</li>
        <li><strong>Sub-processor</strong> — a third party engaged by the Processor to process Personal Data.</li>
        <li><strong>Applicable Law</strong> — the Philippine Data Privacy Act (RA 10173) and, where applicable, the EU GDPR and equivalent foreign laws.</li>
    </ul>

    <h2>2. Subject Matter and Scope</h2>
    <p>The Processor will process Personal Data solely to provide the Service to the Controller, in accordance with the Controller's documented instructions, the Terms, and Applicable Law.</p>

    <h2>3. Categories of Data Subjects and Personal Data</h2>
    <p><strong>Data Subjects:</strong> Faculty, students, deans, supervisors, HR officers, school administrators, IT staff, and other authorized end users of the Controller's tenant.</p>
    <p><strong>Personal Data categories:</strong></p>
    <ul>
        <li>Identification and contact details (name, email, username, role, department, employee/student ID);</li>
        <li>Profile data (date of birth, position, account status, signature image);</li>
        <li>Evaluation content (responses, ratings, free-text comments, peer/dean/self assessments);</li>
        <li>System and audit data (logins, IP, browser, audit-log events);</li>
        <li>Authentication data (hashed passwords, password-reset tokens).</li>
    </ul>

    <h2>4. Processor Obligations</h2>
    <p>The Processor shall:</p>
    <ul>
        <li>Process Personal Data only on documented instructions from the Controller;</li>
        <li>Ensure that personnel authorized to process Personal Data are bound by confidentiality;</li>
        <li>Implement appropriate technical and organizational measures (Section 7);</li>
        <li>Assist the Controller, taking into account the nature of processing, in fulfilling its obligations to respond to Data Subject rights requests;</li>
        <li>Assist the Controller in ensuring compliance with security, breach-notification, and impact-assessment obligations;</li>
        <li>Make available information necessary to demonstrate compliance with this DPA.</li>
    </ul>

    <h2>5. Sub-processors</h2>
    <p>The Controller authorizes the Processor to engage the following Sub-processors:</p>
    <ul>
        <li><strong>Railway, Inc.</strong> — application hosting and managed database (United States/EU regions).</li>
        <li><strong>Resend, Inc.</strong> — transactional email delivery (United States).</li>
    </ul>
    <p>The Processor will give the Controller at least 14 days' notice before adding or replacing a Sub-processor. The Controller may object on reasonable data-protection grounds; if no satisfactory remedy is reached, the Controller may terminate the affected Service.</p>

    <h2>6. International Transfers</h2>
    <p>Where Personal Data is transferred outside the Philippines (or outside the EEA, for EEA Controllers), the Processor will rely on Standard Contractual Clauses, adequacy decisions, or equivalent safeguards required by Applicable Law.</p>

    <h2>7. Security Measures</h2>
    <ul>
        <li><strong>Tenant isolation</strong> — separate database per Controller; cross-tenant access blocked at the framework level.</li>
        <li><strong>Encryption in transit</strong> — TLS 1.2+ for all client and inter-service traffic.</li>
        <li><strong>Encryption at rest</strong> — managed database storage encrypted by the hosting provider.</li>
        <li><strong>Access control</strong> — least-privilege access to production; role-based permissions inside the application; audit logging of administrative events.</li>
        <li><strong>Authentication</strong> — bcrypt-hashed passwords; CSRF protection on all state-changing requests; rate limiting on login and reset endpoints.</li>
        <li><strong>Backups</strong> — automated, encrypted daily backups with point-in-time recovery on supported plans.</li>
        <li><strong>Monitoring</strong> — application and infrastructure logs reviewed for security events.</li>
    </ul>

    <h2>8. Data Subject Rights</h2>
    <p>The Processor will, upon the Controller's reasonable request, provide tooling and assistance to enable the Controller to respond to Data Subject requests for access, rectification, erasure, restriction, portability, and objection.</p>

    <h2>9. Personal Data Breach</h2>
    <p>The Processor will notify the Controller without undue delay (and in any event within 72 hours) after becoming aware of a Personal Data Breach affecting Controller data, providing sufficient information to enable the Controller to meet its own notification obligations.</p>

    <h2>10. Audits</h2>
    <p>The Controller may, at most once per year and on reasonable notice, request information necessary to verify the Processor's compliance with this DPA. Audits will be conducted at the Controller's expense and in a manner that does not unduly disrupt the Processor's operations or the security of other tenants.</p>

    <h2>11. Return or Deletion</h2>
    <p>On termination of the Service, the Processor will, at the Controller's election made within 30 days:</p>
    <ul>
        <li>Provide a one-time export of Controller data in a structured machine-readable format; and/or</li>
        <li>Delete or anonymize Controller Personal Data, subject to retention obligations imposed by Applicable Law.</li>
    </ul>
    <p>After 30 days the Processor may permanently delete the Controller's tenant database without further notice.</p>

    <h2>12. Liability</h2>
    <p>The liability of each party under this DPA is subject to the limitations set out in the Terms of Service.</p>

    <h2>13. Governing Law</h2>
    <p>This DPA is governed by the laws of the Republic of the Philippines, without prejudice to the rights of Data Subjects under their local law.</p>

    <h2>14. Contact</h2>
    <p>Questions about this DPA or to request the executable counterpart copy: contact us via the <a href="{{ route('central.contact') }}">contact page</a> and tag the request "DPA".</p>
@endsection
