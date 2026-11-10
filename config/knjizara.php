<?php

return [

    /*
    | Valuta u kojoj su izražene sve cene u katalogu.
    */
    'currency' => env('KNJIZARA_CURRENCY', 'RSD'),

    /*
    | Stopa PDV-a na knjige (u Srbiji 10%).
    */
    'vat_rate' => (float) env('KNJIZARA_VAT_RATE', 0.10),

    /*
    | Adresa Reverba za pregledač (CSP connect-src), ako se razlikuje od
    | REVERB_HOST/REVERB_PORT, na primer iza nginx-a: wss://knjizara.rs.
    */
    'websocket_origin' => env('WEBSOCKET_ORIGIN'),

];
