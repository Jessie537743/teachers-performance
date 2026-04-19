<?php

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
    ],
];
