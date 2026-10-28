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

];
