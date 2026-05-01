@php
    $pageTitle = 'Terms of Service';
    $pageDescription = 'The terms governing your use of the Teachers Performance Platform.';
    $lastUpdated = 'May 1, 2026';
    $appName = \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance Platform'));
@endphp
@extends('central.legal.layout')

@section('legal')
    <p>These Terms of Service ("Terms") govern your access to and use of {{ $appName }} (the "Service"), operated by the {{ $appName }} team ("we", "us", or "our"). By creating an account, redeeming an activation code, or using the Service, you agree to these Terms. If you do not agree, do not use the Service.</p>

    <h2>1. The Service</h2>
    <p>{{ $appName }} is a multi-tenant software-as-a-service platform that enables schools and academic institutions ("Schools") to administer faculty performance evaluations across student, peer, dean, and self-assessment components, generate reports, and surface AI-driven performance insights.</p>

    <h2>2. Accounts and Eligibility</h2>
    <h3>2.1 School Accounts</h3>
    <p>A School subscribes to the Service by completing checkout, paying the applicable fee, and redeeming the activation code we send to the School's authorized administrator. The redeeming administrator (the "School Admin") binds the School to these Terms.</p>
    <h3>2.2 End-User Accounts</h3>
    <p>The School Admin and authorized HR personnel may create accounts for faculty, students, deans, and other staff within the School ("End Users"). End Users access the Service under their School's tenant subdomain and are governed by these Terms in addition to any policies their School imposes.</p>
    <h3>2.3 Account Security</h3>
    <p>You are responsible for safeguarding your credentials and for any activity under your account. Notify us immediately at <a href="{{ route('central.contact') }}">our contact page</a> of any unauthorized use.</p>

    <h2>3. Subscription, Billing, and Cancellation</h2>
    <ul>
        <li>The Service is offered on monthly or yearly billing cycles. The applicable plan and price are presented at checkout.</li>
        <li>Subscriptions auto-renew at the end of each cycle unless canceled by the School Admin from the in-app billing settings.</li>
        <li>Fees are non-refundable except where required by law. A canceled subscription remains active until the end of the current paid period.</li>
        <li>We may suspend access if a charge fails and is not cured within the grace period stated in your billing settings.</li>
        <li>We may change pricing on prospective renewal cycles with at least 30 days' notice.</li>
    </ul>

    <h2>4. Acceptable Use</h2>
    <p>You agree not to:</p>
    <ul>
        <li>Use the Service to violate any law, regulation, or third-party right;</li>
        <li>Upload content that is unlawful, harassing, defamatory, or that contains malware;</li>
        <li>Attempt to gain unauthorized access to other tenants, our infrastructure, or any account that is not yours;</li>
        <li>Reverse-engineer, decompile, or attempt to extract source code or AI model weights;</li>
        <li>Resell, sublicense, or white-label the Service without our prior written consent;</li>
        <li>Use the Service to harm or unfairly disadvantage individuals being evaluated.</li>
    </ul>

    <h2>5. Your Content</h2>
    <p>"Your Content" means data you or your End Users submit to the Service — including evaluation responses, comments, faculty profiles, course catalogs, signatures, and uploaded files. You retain ownership of Your Content. You grant us a worldwide, non-exclusive, royalty-free license to host, process, transmit, and display Your Content solely to operate, maintain, and improve the Service for you.</p>
    <p>You represent that you have all necessary rights and consents to submit Your Content. You are solely responsible for the accuracy, fairness, and lawfulness of evaluation content posted by your End Users.</p>

    <h2>6. AI Features</h2>
    <p>The Service includes AI-driven features (sentiment analysis, performance prediction, intervention plans, comment summarization). These features are decision-<em>support</em>, not decision-<em>making</em>: human reviewers in your School remain the responsible decision-makers for any HR action. We make no guarantee of accuracy and you agree not to rely on AI output as the sole basis for material employment decisions.</p>

    <h2>7. Tenant Isolation and Data Ownership</h2>
    <p>Each School operates in an isolated tenant database. Your data is not visible to other Schools. We will not access your tenant data except (i) as needed to provide support you request, (ii) to maintain or secure the Service, or (iii) as required by law. See our <a href="{{ route('central.privacy') }}">Privacy Policy</a> for details.</p>

    <h2>8. Intellectual Property</h2>
    <p>The Service, including its source code, UI, AI models, documentation, and trademarks, is owned by us and our licensors. Except for the limited right to use the Service granted in these Terms, no other rights are granted to you.</p>

    <h2>9. Termination</h2>
    <p>You may cancel your subscription at any time. We may suspend or terminate your access if you materially breach these Terms, fail to pay, or use the Service in a way that risks our infrastructure or other tenants. Upon termination we will, on request received within 30 days, provide a one-time export of Your Content; thereafter your tenant database may be permanently deleted.</p>

    <h2>10. Disclaimers</h2>
    <p>THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT. We do not warrant that the Service will be uninterrupted, error-free, or that AI predictions will be accurate.</p>

    <h2>11. Limitation of Liability</h2>
    <p>TO THE MAXIMUM EXTENT PERMITTED BY LAW, OUR AGGREGATE LIABILITY ARISING OUT OF OR RELATED TO THE SERVICE IS LIMITED TO THE FEES PAID BY YOUR SCHOOL TO US IN THE 12 MONTHS PRECEDING THE EVENT GIVING RISE TO LIABILITY. WE ARE NOT LIABLE FOR INDIRECT, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR LOSS OF DATA, REVENUE, OR GOODWILL.</p>

    <h2>12. Indemnification</h2>
    <p>You agree to indemnify and hold us harmless from claims, damages, and expenses arising out of (a) Your Content, (b) your use of the Service in violation of these Terms, or (c) employment-related decisions your School makes based on Service output.</p>

    <h2>13. Governing Law</h2>
    <p>These Terms are governed by the laws of the Republic of the Philippines, without regard to its conflict-of-laws rules. The exclusive venue for disputes is the proper courts of the city where our principal place of business is located, except where you have rights under the Data Privacy Act that grant you a different forum.</p>

    <h2>14. Changes to These Terms</h2>
    <p>We may revise these Terms from time to time. Material changes will be announced via email or in-product notice at least 14 days before they take effect. Continued use of the Service after the effective date constitutes acceptance of the revised Terms.</p>

    <h2>15. Contact</h2>
    <p>Questions about these Terms? Reach us via the <a href="{{ route('central.contact') }}">contact page</a>.</p>
@endsection
