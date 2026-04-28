<?php

/*
|--------------------------------------------------------------------------
| Plan capabilities — single source of truth
|--------------------------------------------------------------------------
|
| `features`     — human-readable strings shown on landing/pricing pages.
| `capabilities` — machine-readable map checked by app code via plan()->has()
|                  (App\Services\PlanFeatures). Add new capabilities here,
|                  then enforce with the `plan.feature:<name>` middleware,
|                  the `@plan('<name>')` blade directive, or `plan()->has()`.
|
| When you add a capability:
|   1. Add the key to ALL three plans below (use `null` for unlimited quotas)
|   2. Document the meaning in the registry comment near the bottom of this file
|
*/

return [
    'free' => [
        'slug'      => 'free',
        'name'      => 'Free',
        'price'     => 0,
        'period'    => 'forever',
        'tagline'   => 'Try the platform with limited evaluations.',
        'features'  => [
            'Up to 50 students',
            'Manual evaluations only',
            'Basic announcements',
            'Email support',
        ],
        'highlight' => false,
        'capabilities' => [
            // boolean gates
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
            // quotas (null = unlimited)
            'max_students'        => 50,
            'max_admin_users'     => 1,
            'max_departments'     => 3,
        ],
    ],
    'pro' => [
        'slug'      => 'pro',
        'name'      => 'Pro',
        'price'     => 99,
        'period'    => 'per month',
        'tagline'   => 'Full evaluation toolkit for growing schools.',
        'features'  => [
            'Unlimited students',
            'AI-powered performance predictions',
            'Sentiment analysis on feedback',
            'All evaluation types (peer, dean, self)',
            'Priority email support',
        ],
        'highlight' => true,
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
        'price'     => 'Custom',
        'period'   => '',
        'tagline'  => 'For multi-campus institutions.',
        'features' => [
            'Everything in Pro',
            'Custom branding',
            'Dedicated success manager',
            'On-premise deployment option',
            'SLA-backed uptime',
        ],
        'highlight' => false,
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

    /*
    |--------------------------------------------------------------------------
    | Capability registry (documentation only — not consumed by code)
    |--------------------------------------------------------------------------
    |
    | ai_predictions      bool   — Faculty performance prediction model
    | sentiment_analysis  bool   — Free-text feedback auto-classification
    | peer_evaluation     bool   — Peer-evaluator workflow + reports
    | self_evaluation     bool   — Self-evaluation form
    | dean_evaluation     bool   — Dean/head evaluation form
    | student_evaluation  bool   — Student-led evaluation form
    | advanced_analytics  bool   — Charts, drill-downs, period comparisons
    | custom_branding     bool   — Tenant logo, colors, email-template overrides
    | export_pdf          bool   — Per-faculty + summary PDF exports
    | export_csv          bool   — Raw CSV downloads
    | priority_support    bool   — Routes support tickets to the priority queue
    | max_students        ?int   — null means unlimited
    | max_admin_users     ?int   — null means unlimited
    | max_departments     ?int   — null means unlimited
    |
    */
];
