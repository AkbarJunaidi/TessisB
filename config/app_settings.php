<?php

/*
| Nilai bawaan Pengaturan Pemilik. Dipakai selama pemilik belum mengubahnya
| (atau tabel app_settings belum ada), jadi aplikasi tetap jalan seperti semula.
*/
return [

    'defaults' => [
        'company_name'            => 'CV. Arindra Production',
        'company_tagline'         => 'Creative House Production',
        'company_address'         => 'Bendul Merisi Selatan 3/102 Surabaya',
        'company_phone'           => '031-8431462',
        'company_whatsapp'        => '081252200899',
        'company_footer_tagline'  => 'Videography | Photography | Live Streaming',
        'company_footer_contact'  => '081217439568, 081252200899',
        'company_website'         => 'www.arindraproduction.co.id',
        'company_instagram'       => '@arindraproduction',
        'company_tiktok'          => '@cvarindraproduction',

        'servis_segera_hari'        => 7,
        'repair_warn_percent'       => 50,
        'repair_rawan_hari'         => 180,
        'repair_rawan_kali'         => 5,
        'repair_rawan_percent'      => 50,
        'repair_gagal_percent'      => 30,
        'location_default_radius'   => 100,
        'upload_max_mb'             => 10,
        'finance_lock_date'         => '',
        'upload_allowed_extensions' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png',

        // Batas input per isian; rentang yang boleh diatur ada di config/limits.php.
        'limit_money_max'    => 999999999999,
        'limit_qty_max'      => 10000,
        'limit_stock_max'    => 100000,
        'limit_duration_max' => 100000,

        'inventory_statuses' => [],
        'finance_categories' => [],

        'project_categories' => ['Wedding', 'Corporate', 'Graduation', 'Live Streaming', 'Product Launch', 'Lainnya'],
    ],

    // Gambar yang bisa diganti pemilik; nilai = file bawaan di folder public.
    'images' => [
        'logo_pdf' => 'image/Arindra.png',
        'kop_atas' => 'image/kopatas.png',
        'kop_bawah' => 'image/kopbawah.png',
    ],

    // Ekstensi yang tidak boleh masuk daftar upload apa pun alasannya.
    'blocked_extensions' => [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'sh',
        'js', 'html', 'htm', 'svg', 'jsp', 'asp', 'aspx', 'cgi', 'pl', 'py', 'dll', 'msi',
    ],

];
