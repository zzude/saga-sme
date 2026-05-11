<?php

return [
    'env'            => env('BILLPLZ_ENV', 'sandbox'),
    'api_key'        => env('BILLPLZ_API_KEY', ''),
    'x_signature'    => env('BILLPLZ_X_SIGNATURE', ''),
    'collection_id'  => env('BILLPLZ_COLLECTION_ID', ''),

    'urls' => [
        'sandbox'    => 'https://www.billplz-sandbox.com/api/v3',
        'production' => 'https://www.billplz.com/api/v3',
    ],

    'payment_url' => [
        'sandbox'    => 'https://www.billplz-sandbox.com/bills',
        'production' => 'https://www.billplz.com/bills',
    ],
];
