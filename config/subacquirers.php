<?php

return [
    'map' => [
        'subadqa' => \App\Services\SubadqAService::class,
        'subadqb' => \App\Services\SubadqBService::class,
    ],
    'defaults' => [
        'subadqa' => [
            'base_url' => env('SUBADQA_BASE_URL', 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io'),
            'merchant_id' => env('SUBADQA_MERCHANT_ID', 'm123'),
        ],
        'subadqb' => [
            'base_url' => env('SUBADQB_BASE_URL', 'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io'),
            'merchant_id' => env('SUBADQB_MERCHANT_ID', 'm123'),
        ],
    ],
];
