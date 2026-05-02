<?php

return [

    'type' => env('SSO_TYPE', 'client'),

    'server_url' => env('SSO_SERVER_URL', 'http://localhost:8000'),

    'server_internal_url' => env('SSO_SERVER_INTERNAL_URL'),

    'client_id' => env('SSO_CLIENT_ID', ''),
    'client_secret' => env('SSO_CLIENT_SECRET', ''),

    'token_expiration' => env('SSO_TOKEN_EXPIRATION', 60),

];
