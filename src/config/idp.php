<?php

use App\Services\Idp\LocalTemplateIdpGenerator;

return [

    /*
    |--------------------------------------------------------------------------
    | IDP Engine
    |--------------------------------------------------------------------------
    |
    | Selects which IdpGenerator implementation is bound when a controller
    | type-hints App\Contracts\IdpGenerator. Today only `local` is wired.
    | Adding a real LLM driver later is two steps: write a class implementing
    | IdpGenerator, register it under `drivers` here, then set IDP_ENGINE.
    |
    */

    'engine' => env('IDP_ENGINE', 'local'),

    'drivers' => [
        'local' => LocalTemplateIdpGenerator::class,
        // 'anthropic' => \App\Services\Idp\AnthropicIdpGenerator::class,
        // 'openai'    => \App\Services\Idp\OpenAiIdpGenerator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Credentials (used by future LLM drivers)
    |--------------------------------------------------------------------------
    |
    | Wired now so swapping IDP_ENGINE later only requires setting the key in
    | the environment — no config edit, no redeploy of code.
    |
    */

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model'   => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thresholds (used by LocalTemplateIdpGenerator)
    |--------------------------------------------------------------------------
    */

    'thresholds' => [
        'strength_min'      => 4.5,   // category_avg ≥ this → counted as strength
        'growth_max'        => 4.0,   // category_avg < this → counted as growth area
        'top_n_strengths'   => 3,
        'top_n_growth'      => 3,
    ],
];
