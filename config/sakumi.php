<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Receipt HMAC Key
    |--------------------------------------------------------------------------
    |
    | Dedicated key for receipt verification codes. Set this BEFORE rotating
    | APP_KEY so that existing printed receipts remain verifiable. Falls back
    | to APP_KEY when not set.
    |
    */
    'receipt_hmac_key' => env('RECEIPT_HMAC_KEY'),

];
