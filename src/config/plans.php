<?php

/*
|--------------------------------------------------------------------------
| Plan capabilities + pricing — single source of truth
|--------------------------------------------------------------------------
|
| `prices.monthly` / `prices.yearly` — recurring billing amounts.
| `price` / `period`                 — legacy convenience (mirrors monthly).
| `features`                         — display strings shown on landing/pricing.
| `capabilities`                     — machine-readable map checked by app code
|                                      via plan()->has() (App\Services\PlanFeatures).
|
| Yearly is priced at 10× monthly (≈17% off, "2 months free").
|
*/

return [
    'free' => [
        'slug'      => 'free',
        'name'      => 'Free',
        'tagline'   => 'Try the platform with limited evaluations.',
        'highlight' => false,
        'prices'    => [
            'monthly' => 0,
            'yearly'  => 0,
        ],
        // Legacy convenience (mirrors monthly):
        'price'  => 0,
        'period' => 'forever',
        'features'  => [
            'Up to 50 students',
            'Manual evaluations only',
            'Basic announcements',
            'Email support',
        ],
        'capabilities' => [
            'ai_predictions'      => false,
            'sentiment_analysis'  => false,
            'peer_evaluation'     => false,
            'self_evaluation'     => true,
            'dean_evaluation'     => true,
            'student_evaluation'  => true,
            'advanced_analytics'  => false,
            'custom_branding'     => false,
            'export_pdf'          => false,
            'export_csv'          => true,
            'priority_support'    => false,
            'max_students'        => 50,
            'max_admin_users'     => 1,
            'max_departments'     => 3,
        ],
    ],

    'pro' => [
        'slug'      => 'pro',
        'name'      => 'Pro',
        'tagline'   => 'Full evaluation toolkit for growing schools.',
        'highlight' => true,
        'prices'    => [
            'monthly' => 99,
            'yearly'  => 990,    // 10 × monthly = 2 months free
        ],
        'price'  => 99,
        'period' => 'per month',
        'features'  => [
            'Unlimited students',
            'AI-powered performance predictions',
            'Sentiment analysis on feedback',
            'All evaluation types (peer, dean, self)',
            'Priority email support',
        ],
        'capabilities' => [
            'ai_predictions'      => true,
            'sentiment_analysis'  => true,
            'peer_evaluation'     => true,
            'self_evaluation'     => true,
            'dean_evaluation'     => true,
            'student_evaluation'  => true,
            'advanced_analytics'  => true,
            'custom_branding'     => false,
            'export_pdf'          => true,
            'export_csv'          => true,
            'priority_support'    => true,
            'max_students'        => null,
            'max_admin_users'     => 5,
            'max_departments'     => null,
        ],
    ],

    'enterprise' => [
        'slug'      => 'enterprise',
        'name'      => 'Enterprise',
        'tagline'   => 'For multi-campus institutions.',
        'highlight' => false,
        'prices'    => [
            'monthly' => null,   // negotiated
            'yearly'  => null,
        ],
        'price'  => 'Custom',
        'period' => '',
        'features' => [
            'Everything in Pro',
            'Custom branding',
            'Dedicated success manager',
            'On-premise deployment option',
            'SLA-backed uptime',
        ],
        'capabilities' => [
            'ai_predictions'      => true,
            'sentiment_analysis'  => true,
            'peer_evaluation'     => true,
            'self_evaluation'     => true,
            'dean_evaluation'     => true,
            'student_evaluation'  => true,
            'advanced_analytics'  => true,
            'custom_branding'     => true,
            'export_pdf'          => true,
            'export_csv'          => true,
            'priority_support'    => true,
            'max_students'        => null,
            'max_admin_users'     => null,
            'max_departments'     => null,
        ],
    ],
];
