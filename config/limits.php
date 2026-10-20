<?php

// Rentang batas input yang boleh diatur Super Admin di Pengaturan: [terendah, tertinggi].
// Tertinggi menjaga kolom database (uang decimal(15,2) = 13 digit) tetap muat. Nilai bawaan ada di config/app_settings.php.
return [
    'range' => [
        'money_max'    => [1000000, 9999999999999],
        'qty_max'      => [100, 1000000],
        'stock_max'    => [100, 1000000],
        'duration_max' => [1440, 1000000],
    ],
];
