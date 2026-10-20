<?php

// Batas angka input. Dipakai FormRequest (backend) dan atribut max di view (frontend), jadi selalu sama.
return [
    // Rupiah: kolom DB decimal(15,2) muat 13 digit; 12 digit disisakan agar hasil penjumlahan tetap muat.
    'money_max'    => 999999999999,

    // Jumlah per transaksi: item Pembelian, penerimaan, retur, Booking Alat.
    'qty_max'      => 10000,

    // Jumlah stok barang dan barang per Surat Jalan / peminjaman.
    'stock_max'    => 100000,

    // Estimasi durasi project (menit).
    'duration_max' => 100000,
];
