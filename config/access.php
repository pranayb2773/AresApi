<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ip Whitelist
    |--------------------------------------------------------------------------
    |
    | This option controls the IP addresses which access api endpoints.
    | By default access given to all IP addresses. You may configure this
    | in .env file or here. IP addresses should be seperated by ';'.
    |
    */

    'ipWhitelist' => env('IP_WHITELIST', 'all'),
];
