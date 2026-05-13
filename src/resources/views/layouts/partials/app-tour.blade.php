{{--
    Guided in-app tour powered by Driver.js (5KB, MIT).

    Behaviour:
      - Auto-starts ONCE per role on a user's first login that lands on the
        dashboard (skipped if the user has already completed their role's
        tour, recorded in users.completed_tours).
      - Manual replay any time: click the "Take the Tour" item in the sidebar.
      - "Finish" or "Skip" both POST to /tour/complete so we stop auto-showing.

    Per-role step sets are defined inline below. Steps reference elements via
    `[data-tour="..."]` markers placed on the relevant DOM nodes (sidebar
    items, announcement bell, etc.). Steps that target a missing element are
    skipped at runtime — handy when a user's role hides certain sidebar items.
--}}
@auth
@php
    $user      = auth()->user();
    $roles     = (array) $user->roles;
    $tourKey   = match (true) {
        in_array('admin', $roles, true)            => 'admin',
        in_array('dean', $roles, true),
        in_array('head', $roles, true)             => 'dean',
        in_array('human_resource', $roles, true)   => 'hr',
        in_array('faculty', $roles, true)          => 'faculty',
        in_array('student', $roles, true)          => 'student',
        default                                     => null,
    };
    $completed = (array) ($user->completed_tours ?? []);
    $shouldAutoStart = $tourKey
        && !in_array($tourKey, $completed, true)
        && request()->routeIs('dashboard');
@endphp

@if ($tourKey)
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>

<style>
    /* Match the app's design language for the popover */
    .driver-popover { border-radius: 16px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18); }
    .driver-popover-title { font-weight: 700; color: #0f172a; }
    .driver-popover-description { color: #334155; font-size: 13px; line-height: 1.55; }
    .driver-popover-progress-text { color: #64748b; font-size: 11px; font-weight: 600; }
    .driver-popover-next-btn,
    .driver-popover-prev-btn,
    .driver-popover-close-btn {
        border-radius: 10px !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        padding: 8px 14px !important;
        text-shadow: none !important;
    }
    .driver-popover-next-btn {
        background: linear-gradient(to right, #2563eb, #1d4ed8) !important;
        color: #fff !important;
        border: none !important;
    }
    .driver-popover-prev-btn {
        background: #f1f5f9 !important;
        color: #334155 !important;
        border: 1px solid #e2e8f0 !important;
    }
</style>

<script>
(function () {
    'use strict';

    // ----- Per-role tour definitions ---------------------------------------
    const COMMON_END_STEP = {
        popover: {
            title: 'You\'re all set! 🎉',
            description: 'You can replay this tour anytime from <strong>Take the Tour</strong> in the sidebar. Visit the <strong>Help Center</strong> for detailed guides.',
        }
    };

    const TOURS = {
        admin: [
            {
                popover: {
                    title: 'Welcome, Administrator',
                    description: 'Quick tour of the spots you\'ll use most. About 60 seconds. You can skip anytime.',
                },
            },
            { element: '[data-tour="dashboard"]',     popover: { title: 'Dashboard', description: 'KPIs at a glance: active periods, faculty / student counts, compliance, recent activity.' } },
            { element: '[data-tour="analytics"]',     popover: { title: 'Analytics', description: 'Performance trends across departments, AI predictions, and individual personnel history.' } },
            { element: '[data-tour="evaluate"]',      popover: { title: 'Evaluations',  description: 'Open evaluation periods, manage criteria, and run reports.' } },
            { element: '[data-tour="faculty"]',       popover: { title: 'Faculty',      description: 'Add, bulk-upload, archive, or reactivate faculty members.' } },
            { element: '[data-tour="students"]',      popover: { title: 'Students',     description: 'Same operations as Faculty, plus course / year / section assignment.' } },
            { element: '[data-tour="settings"]',      popover: { title: 'Settings',     description: 'Institution branding, signatures, API integration, and role permissions.' } },
            { element: '[data-tour="announcements"]', popover: { title: 'Announcements', description: 'Unread updates appear with a badge. Click to read or acknowledge.' } },
            { element: '[data-tour="help"]',          popover: { title: 'Help Center',  description: 'Setup guide and FAQ — handy when you\'re configuring the system for the first time.' } },
            COMMON_END_STEP,
        ],
        dean: [
            { popover: { title: 'Welcome, Dean', description: 'Quick tour of your department workspace. About 45 seconds.' } },
            { element: '[data-tour="dashboard"]',     popover: { title: 'Dashboard', description: 'Department-scoped KPIs: faculty under your wing, pending registrations, evaluation progress.' } },
            { element: '[data-tour="analytics"]',     popover: { title: 'Analytics', description: 'Department performance trends and AI-powered insights.' } },
            { element: '[data-tour="evaluate"]',      popover: { title: 'Evaluations',  description: 'Submit Dean evaluations for faculty in your department.' } },
            { element: '[data-tour="announcements"]', popover: { title: 'Announcements', description: 'Read system + department announcements here.' } },
            { element: '[data-tour="help"]',          popover: { title: 'Help Center', description: 'Step-by-step guides for every dean workflow.' } },
            COMMON_END_STEP,
        ],
        hr: [
            { popover: { title: 'Welcome, HR', description: 'Quick tour of your compliance and development workspace.' } },
            { element: '[data-tour="dashboard"]',     popover: { title: 'Dashboard', description: 'Compliance monitoring across the institution at a glance.' } },
            { element: '[data-tour="analytics"]',     popover: { title: 'Analytics', description: 'Performance trends — useful when reviewing intervention needs.' } },
            { element: '[data-tour="faculty"]',       popover: { title: 'Faculty', description: 'Manage faculty records and generate performance certificates.' } },
            { element: '[data-tour="announcements"]', popover: { title: 'Announcements', description: 'Post institution-wide announcements from here.' } },
            { element: '[data-tour="help"]',          popover: { title: 'Help Center', description: 'HR-specific workflows and recommended best practices.' } },
            COMMON_END_STEP,
        ],
        faculty: [
            { popover: { title: 'Welcome', description: 'Two-minute tour of your evaluation workspace.' } },
            { element: '[data-tour="dashboard"]',     popover: { title: 'Your Dashboard', description: 'Your performance summary and any tasks waiting for you.' } },
            { element: '[data-tour="evaluate"]',      popover: { title: 'Evaluations',  description: 'Complete your self-evaluation here, plus peer evaluations when an open period is running.' } },
            { element: '[data-tour="announcements"]', popover: { title: 'Announcements', description: 'Watch this bell — important deadlines and updates appear here.' } },
            { element: '[data-tour="help"]',          popover: { title: 'Help Center', description: 'Detailed guides if you get stuck.' } },
            COMMON_END_STEP,
        ],
        student: [
            { popover: { title: 'Welcome', description: 'One-minute tour. You\'ll evaluate your instructors here each period.' } },
            { element: '[data-tour="dashboard"]',     popover: { title: 'Your Dashboard', description: 'Shows the open evaluation period and which subjects still need your input.' } },
            { element: '[data-tour="evaluate"]',      popover: { title: 'Evaluations', description: 'Tap a subject row to rate the instructor on a 1–5 scale. Honest, thoughtful answers help the school improve.' } },
            { element: '[data-tour="announcements"]', popover: { title: 'Announcements', description: 'Reminders about open periods and deadlines appear here.' } },
            { element: '[data-tour="help"]',          popover: { title: 'Help Center', description: 'Quick guides on how to fill out evaluations.' } },
            COMMON_END_STEP,
        ],
    };

    // ----- Build & start ---------------------------------------------------
    const tourKey  = @json($tourKey);
    const autoStart = @json($shouldAutoStart);
    const csrf     = @json(csrf_token());
    const completeUrl = @json(route('tour.complete'));
    const allSteps = TOURS[tourKey] || [];

    // Filter out steps whose target element doesn't exist on this page.
    function visibleSteps() {
        return allSteps.filter(s => !s.element || document.querySelector(s.element));
    }

    function markComplete() {
        try {
            fetch(completeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ tour: tourKey }),
            });
        } catch (_) { /* fire-and-forget; offline is fine */ }
    }

    function startTour() {
        const steps = visibleSteps();
        if (steps.length === 0 || !window.driver) {
            return;
        }
        const d = window.driver.js.driver({
            showProgress: true,
            allowClose: true,
            nextBtnText: 'Next →',
            prevBtnText: '← Back',
            doneBtnText: 'Finish',
            onDestroyStarted: () => {
                // Fires on Skip + on Finish. Either way, mark complete.
                markComplete();
                d.destroy();
            },
            steps,
        });
        d.drive();
    }

    // Auto-start on first relevant page load (we gate it server-side, so
    // by the time we get here we already know the user hasn't seen it).
    if (autoStart) {
        // Slight delay so the page settles and target elements are positioned.
        setTimeout(startTour, 500);
    }

    // Wire up any "Take the Tour" launcher on the page (sidebar item).
    document.querySelectorAll('[data-tour-launcher]').forEach(btn => {
        btn.addEventListener('click', startTour);
    });
})();
</script>
@endif
@endauth
