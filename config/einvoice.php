<?php

return [

    /*
    |------------------------------------------------------------------
    | Environment
    | sandbox = testing | production = live LHDN
    |------------------------------------------------------------------
    */
    'env' => env('EINVOICE_ENV', 'sandbox'),

    'mock_mode' => env('EINVOICE_MOCK', true),

    /*
    |------------------------------------------------------------------
    | API URLs
    |------------------------------------------------------------------
    */
    'urls' => [
        'sandbox' => [
            'identity' => 'https://preprod.myinvois.hasil.gov.my/connect/token',
            'api'      => 'https://preprod.myinvois.hasil.gov.my/api/v1.0',
        ],
        'production' => [
            'identity' => 'https://myinvois.hasil.gov.my/connect/token',
            'api'      => 'https://myinvois.hasil.gov.my/api/v1.0',
        ],
    ],

    /*
    |------------------------------------------------------------------
    | Credentials
    |------------------------------------------------------------------
    */
    'client_id'     => env('MYINVOIS_CLIENT_ID', ''),
    'client_secret' => env('MYINVOIS_CLIENT_SECRET', ''),

    /*
    |------------------------------------------------------------------
    | Supplier Info (your company)
    |------------------------------------------------------------------
    */
    'supplier' => [
        'tin'      => env('MYINVOIS_SUPPLIER_TIN', ''),
        'brn'      => env('MYINVOIS_SUPPLIER_BRN', ''),
        'sst'      => env('MYINVOIS_SUPPLIER_SST', 'NA'),
        'name'     => env('MYINVOIS_SUPPLIER_NAME', ''),
        'email'    => env('MYINVOIS_SUPPLIER_EMAIL', ''),
        'phone'    => env('MYINVOIS_SUPPLIER_PHONE', ''),
        'msic'     => env('MYINVOIS_SUPPLIER_MSIC', ''),
        'msic_desc'=> env('MYINVOIS_SUPPLIER_MSIC_DESC', ''),
        'address'  => [
            'line1'    => env('MYINVOIS_SUPPLIER_ADDR1', ''),
            'line2'    => env('MYINVOIS_SUPPLIER_ADDR2', ''),
            'line3'    => env('MYINVOIS_SUPPLIER_ADDR3', ''),
            'city'     => env('MYINVOIS_SUPPLIER_CITY', ''),
            'postcode' => env('MYINVOIS_SUPPLIER_POSTCODE', ''),
            'state'    => env('MYINVOIS_SUPPLIER_STATE', ''),
            'country'  => env('MYINVOIS_SUPPLIER_COUNTRY', 'MYS'),
        ],
    ],

    /*
    |------------------------------------------------------------------
    | Submission Settings
    |------------------------------------------------------------------
    */
    'token_cache_key'    => 'myinvois_access_token',
    'token_cache_ttl'    => 50,   // minutes (token expires 60min)
    'poll_delay_seconds' => 10,   // delay before first status poll
    'max_retries'        => 3,
];