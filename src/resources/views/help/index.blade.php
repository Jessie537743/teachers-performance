@extends('layouts.app')
@section('title', 'Help Center')
@section('page-title', 'Help Center')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Help Center</h1>
        <p class="text-sm text-gray-500 mt-1">Setup guides, role-based instructions, and frequently asked questions.</p>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
    {{-- Tab Navigation --}}
    <div class="flex border-b border-gray-200 overflow-x-auto">
        {{-- Setup Guide tab hidden for now
        <a href="{{ route('help.index', ['tab' => 'setup-guide']) }}"
           class="px-6 py-3.5 font-semibold text-sm whitespace-nowrap transition {{ $tab === 'setup-guide' ? 'text-blue-600 border-b-[3px] border-blue-600' : 'text-gray-700 border-b-[3px] border-transparent hover:text-blue-600' }}">
            Setup Guide
        </a>
        --}}
        <a href="{{ route('help.index', ['tab' => 'help-guide']) }}"
           class="px-6 py-3.5 font-semibold text-sm whitespace-nowrap transition {{ $tab === 'help-guide' ? 'text-blue-600 border-b-[3px] border-blue-600' : 'text-gray-700 border-b-[3px] border-transparent hover:text-blue-600' }}">
            Help Guide
        </a>
        <a href="{{ route('help.index', ['tab' => 'faq']) }}"
           class="px-6 py-3.5 font-semibold text-sm whitespace-nowrap transition {{ $tab === 'faq' ? 'text-blue-600 border-b-[3px] border-blue-600' : 'text-gray-700 border-b-[3px] border-transparent hover:text-blue-600' }}">
            FAQ
        </a>
    </div>

    {{-- Tab Content --}}
    <div class="p-6">

        {{-- ============================================================ --}}
        {{-- TAB 1: SETUP GUIDE                                           --}}
        {{-- ============================================================ --}}
        @if($tab === 'setup-guide')
        <div class="max-w-3xl">
            <p class="text-sm text-gray-600 mb-8">Follow these steps to get the evaluation system up and running for your institution.</p>

            {{-- Step 1 --}}
            <div class="flex gap-4 mb-8">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white grid place-items-center font-bold text-sm">1</div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base mb-1">System Requirements & Initial Setup</h3>
                    <p class="text-sm text-gray-600 mb-3">Ensure the following prerequisites are in place before configuring the system:</p>
                    <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1.5">
                        <li><strong>Server:</strong> PHP 8.3+, MySQL 8.0+, Composer, Node.js (for asset compilation)</li>
                        <li><strong>Environment:</strong> Copy <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">.env.example</code> to <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">.env</code> and configure your database credentials</li>
                        <li><strong>Database:</strong> Run <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">php artisan migrate --seed</code> to create tables and seed default data</li>
                        <li><strong>Default Admin:</strong> Login with <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">admin@sample.com</code> using the default password, then change it immediately</li>
                        <li><strong>Docker (optional):</strong> Run <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">docker-compose up -d</code> to start all services including MySQL and the ML API</li>
                    </ul>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="flex gap-4 mb-8">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white grid place-items-center font-bold text-sm">2</div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base mb-1">Configure Evaluation Periods</h3>
                    <p class="text-sm text-gray-600 mb-3">Evaluation periods control when faculty, students, and deans can submit evaluations.</p>
                    <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1.5">
                        <li>Navigate to <strong>Evaluation Periods</strong> from the sidebar</li>
                        <li>Click <strong>+ Add Period</strong> and fill in the semester, school year, and date range</li>
                        <li>Only <strong>one period</strong> can be open at a time &mdash; the system enforces this automatically</li>
                        <li>When a period ends, students are automatically promoted to the next year level</li>
                        <li>Closed periods preserve all submitted evaluation data for historical reporting</li>
                    </ul>
                </div>
            </div>

            {{-- Step 3 --}}
            <div class="flex gap-4 mb-8">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white grid place-items-center font-bold text-sm">3</div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base mb-1">Manage Departments, Faculty & Students</h3>
                    <p class="text-sm text-gray-600 mb-3">Set up the organizational structure and user accounts.</p>
                    <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1.5">
                        <li><strong>Departments:</strong> Create teaching and non-teaching departments under <strong>Departments</strong> in the sidebar</li>
                        <li><strong>Faculty:</strong> Add faculty individually or use <strong>Upload CSV</strong> for bulk import. Assign each to a department and set their role/position (Faculty, Dean/Head, Program Chair, Staff)</li>
                        <li><strong>Students:</strong> Add students individually or bulk upload. Assign course, year level, and section so the system can match them to subjects automatically</li>
                        <li><strong>Courses:</strong> Define academic programs (e.g., BSIT, BSEd) under <strong>Courses</strong></li>
                        <li><strong>Subjects:</strong> Create subjects and assign faculty teaching them. Students are matched to subjects by course, year level, and section</li>
                    </ul>
                </div>
            </div>

            {{-- Step 4 --}}
            <div class="flex gap-4 mb-8">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white grid place-items-center font-bold text-sm">4</div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base mb-1">Set Up Evaluation Criteria</h3>
                    <p class="text-sm text-gray-600 mb-3">Define the rubrics used in evaluations.</p>
                    <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1.5">
                        <li>Navigate to <strong>Criteria</strong> from the sidebar</li>
                        <li>Create criteria categories for each evaluation type: student, dean/supervisor, self, and peer</li>
                        <li>Each criterion can target specific personnel types: teaching, non-teaching, or academic administrators</li>
                        <li>Add questions under each criterion &mdash; these are the individual items evaluators will rate on a 1&ndash;5 Likert scale</li>
                        <li>The system uses weighted averages: Student (40%), Dean (40%), Self (10%), Peer (10%)</li>
                    </ul>
                </div>
            </div>

            {{-- Step 5 --}}
            <div class="flex gap-4 mb-8">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-600 text-white grid place-items-center font-bold text-sm">5</div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base mb-1">Train the ML Model (Optional)</h3>
                    <p class="text-sm text-gray-600 mb-3">The system includes a Random Forest classifier for predicting faculty performance trends.</p>
                    <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1.5">
                        <li>Navigate to <strong>Model Training</strong> under the AI section in the sidebar</li>
                        <li>Ensure the ML API service is running (Docker or Railway deployment)</li>
                        <li>The model requires at least <strong>2 evaluation periods</strong> of submitted data with human-labeled performance levels</li>
                        <li>Select a scope (all terms or a specific semester/year) and click <strong>Train Random Forest</strong></li>
                        <li>After training, accuracy metrics (Accuracy, Precision, Recall, F1) and feature importance are displayed</li>
                        <li>Predictions become available in the <strong>Analytics</strong> page for individual faculty members</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 2: HELP GUIDE                                            --}}
        {{-- ============================================================ --}}
        @elseif($tab === 'help-guide')
        <div>
            <p class="text-sm text-gray-600 mb-6">Select your role below to see what you can do in the system.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Administrator --}}
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Admin</span>
                        <h3 class="font-bold text-slate-900">Full System Management</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">Administrators have complete control over the system.</p>
                    <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                        <li>Manage departments, faculty, students, courses, and subjects</li>
                        <li>Create and manage evaluation periods (open/close)</li>
                        <li>Configure evaluation criteria and questions</li>
                        <li>View all reports: individual, department, low performance, and sustained low performance</li>
                        <li>Access system-wide analytics and performance distribution</li>
                        <li>Manage roles and permissions for all user types</li>
                        <li>Configure system settings (app name, logo)</li>
                        <li>Train the ML model and view prediction accuracy</li>
                        <li>Monitor evaluation compliance across all departments</li>
                    </ul>
                </div>

                {{-- Dean / Head --}}
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Dean / Head</span>
                        <h3 class="font-bold text-slate-900">Department Evaluation</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">Deans and department heads evaluate faculty in their department.</p>
                    <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                        <li>View department dashboard with faculty performance summary</li>
                        <li>Submit supervisor evaluations for each faculty member</li>
                        <li>View department-level analytics and performance trends</li>
                        <li>Access individual evaluation reports for faculty in your department</li>
                        <li>Generate performance reports and intervention suggestions</li>
                        <li>Monitor evaluation compliance within your department</li>
                    </ul>
                </div>

                {{-- Faculty --}}
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700">Faculty</span>
                        <h3 class="font-bold text-slate-900">Self & Peer Evaluation</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">Faculty members complete self-assessments and evaluate peers.</p>
                    <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                        <li>Complete your <strong>self-evaluation</strong> each open evaluation period</li>
                        <li>Submit <strong>peer evaluations</strong> for colleagues in your department</li>
                        <li>View your evaluation dashboard and performance history</li>
                        <li>Evaluations use the criteria and Likert scale defined by the administrator</li>
                    </ul>
                </div>

                {{-- Student --}}
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Student</span>
                        <h3 class="font-bold text-slate-900">Course Evaluation</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">Students evaluate their instructors for each subject.</p>
                    <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                        <li>View your dashboard showing subjects matched to your course, year level, and section</li>
                        <li>Submit evaluations for each faculty member teaching your subjects</li>
                        <li>Evaluations are <strong>anonymous</strong> &mdash; faculty cannot see who rated them</li>
                        <li>You can only submit once per faculty per subject per evaluation period</li>
                        <li>Login using your <strong>Student ID</strong> or email address</li>
                    </ul>
                </div>

                {{-- HR / Staff --}}
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 md:col-span-2">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">HR / Staff</span>
                        <h3 class="font-bold text-slate-900">Monitoring & Reports</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">Human Resource officers and institutional leaders monitor performance across the institution.</p>
                    <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                        <li>View the HR dashboard with institution-wide personnel statistics</li>
                        <li>Monitor evaluation compliance across all departments</li>
                        <li>Access reports: department performance, low-performance personnel, sustained low performance</li>
                        <li>View analytics with performance distribution and trends</li>
                        <li>Roles include: Human Resource, VP Academic, VP Admin, School President</li>
                    </ul>
                </div>

            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 3: FAQ                                                   --}}
        {{-- ============================================================ --}}
        @elseif($tab === 'faq')
        <div class="max-w-3xl">

            {{-- Evaluation Process --}}
            <h3 class="text-base font-bold text-slate-900 mb-3">Evaluation Process</h3>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>How does the evaluation process work?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    The administrator opens an evaluation period for a specific semester and school year. During the open period, students evaluate their instructors, deans evaluate faculty in their department, faculty complete self-evaluations, and peers evaluate colleagues. Once the period ends, all data is locked and used for performance analysis, reporting, and ML prediction.
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>Who evaluates whom?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    <strong>Students</strong> evaluate faculty who teach their subjects. <strong>Deans/Heads</strong> evaluate faculty in their department. <strong>Faculty</strong> complete self-evaluations and peer evaluations for colleagues in the same department. The weighted formula is: Student (40%) + Dean/Supervisor (40%) + Self (10%) + Peer (10%).
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>When are evaluations conducted?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    Evaluations are only available when the administrator opens an evaluation period. Each period has a start and end date. Only one period can be active at a time. When the period ends, it is automatically closed and no more submissions are accepted.
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-6">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>What happens after evaluations are submitted?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    Submitted evaluations are aggregated into performance scores per faculty member. The system calculates weighted averages and assigns performance levels (Excellent, Very Satisfactory, Satisfactory, Fair, Poor for teaching; different scales for non-teaching and academic administrators). Results appear on dashboards, in reports, and in analytics.
                </div>
            </div>

            {{-- Account Management --}}
            <h3 class="text-base font-bold text-slate-900 mb-3">Account Management</h3>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>How do I change my password?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    Click your name in the top-right corner and select <strong>Change Password</strong> from the dropdown menu. Enter your current password, then type and confirm your new password. New accounts created by the administrator are required to change their password on first login.
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>What if I forgot my password?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    On the login page, click <strong>Forgot your password?</strong> and enter your email address. If email is configured, you will receive a password reset link. Alternatively, contact your administrator to have your password reset manually.
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-6">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>How do I update my profile information?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    Click your name in the top-right corner and select <strong>My Account</strong>. You can update your display name and email address. Role assignments and department changes can only be made by an administrator.
                </div>
            </div>

            {{-- Reports & Analytics --}}
            <h3 class="text-base font-bold text-slate-900 mb-3">Reports & Analytics</h3>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>What reports are available?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    The system offers several report types: <strong>Employee Comments</strong> (qualitative feedback with sentiment classification), <strong>Individual Evaluation Reports</strong> (per-faculty itemized breakdown), <strong>Department Report</strong> (aggregate performance per department), <strong>Low Performance Personnel</strong> (faculty below threshold), and <strong>Sustained Low Performance</strong> (faculty with consistently low scores across multiple periods).
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>How are performance levels calculated?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    Performance levels are determined by the weighted average score. For <strong>teaching</strong> personnel: Excellent (4.50&ndash;5.00), Very Satisfactory (3.50&ndash;4.49), Satisfactory (2.50&ndash;3.49), Fair (1.50&ndash;2.49), Poor (below 1.50). Non-teaching and academic administrator personnel use different label sets but similar numerical thresholds. The weights are Student (40%), Dean (40%), Self (10%), Peer (10%).
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-6">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>Who can access the reports?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    Report access is controlled by permissions. Administrators can view all reports system-wide. Deans and department heads can view reports for their department. HR officers and institutional leaders (VP Academic, School President) can access institution-wide reports. Faculty and students do not have access to administrative reports.
                </div>
            </div>

            {{-- ML Predictions --}}
            <h3 class="text-base font-bold text-slate-900 mb-3">ML Predictions</h3>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>What is the ML prediction feature?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    The system includes a <strong>Random Forest classifier</strong> that predicts faculty performance trends based on historical evaluation data. It uses four features: average evaluation score, number of evaluation responses, previous term score, and improvement rate. Predictions are displayed in the Analytics page alongside actual performance data.
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>How accurate are the predictions?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    Accuracy depends on the volume and diversity of evaluation data. After training, the Model Training page displays metrics: Accuracy, Precision, Recall, and F1 Score. The model uses an 80/20 train/test split with faculty-grouped separation to prevent data leakage. More evaluation periods with varied performance levels lead to better accuracy.
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-3">
                <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-slate-900 hover:bg-gray-50 transition">
                    <span>What data is used for predictions?</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="hidden px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    The model trains on human-labeled evaluation data from three sources: self-evaluations, dean/supervisor evaluations, and peer evaluations. Each must have a <code class="bg-gray-100 px-1 py-0.5 rounded text-xs">performance_level</code> label and a valid <code class="bg-gray-100 px-1 py-0.5 rounded text-xs">total_average</code> score. Student evaluations are not used for training because they do not carry human-assigned performance labels.
                </div>
            </div>

        </div>
        @endif

    </div>
</div>
@endsection
