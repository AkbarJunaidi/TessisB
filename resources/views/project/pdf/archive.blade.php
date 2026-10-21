{{-- PDF arsip project (ProjectArchiveService). Dibuat sebelum project dihapus; acuan isi ulang manual. --}}
@php
    $rp = fn ($value) => \App\Support\Money::formatRupiah($value);
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y') : '-';
    $source = fn ($item) => match ($item->source_type) {
        'purchase' => 'Pembelian',
        'repair'   => 'Servis',
        default    => 'Manual',
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arsip Project - {{ $project->name }}</title>
    <style>
        @page { size: a4 portrait; margin: 1.6cm 1.5cm 2.1cm 1.5cm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #222; line-height: 1.35; }
        h1 { font-size: 15pt; margin: 0 0 2px; }
        h2 { font-size: 10pt; margin: 16px 0 5px; padding-bottom: 3px; border-bottom: 1px solid #999; page-break-after: avoid; }
        .muted { color: #666; font-size: 8pt; }
        .note { border: 1px solid #bbb; background: #fafafa; padding: 5px 7px; margin-top: 8px; font-size: 8pt; }

        /* Tabel panjang: kolom tetap, teks panjang turun baris, judul kolom berulang di tiap halaman, baris tidak terpotong. */
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #bbb; padding: 3px 5px; vertical-align: top; word-wrap: break-word; }
        th { background: #eee; text-align: left; }
        .kv td:first-child { width: 30%; background: #f5f5f5; font-weight: bold; }
        .r { text-align: right; }
        .c { text-align: center; }
        .total td { font-weight: bold; background: #f5f5f5; }

        .footer { position: fixed; bottom: -1.5cm; left: 0; right: 0; text-align: center; font-size: 7.5pt; color: #777; }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">Arsip Project &middot; {{ $project->name }} &middot; Halaman <span class="pagenum"></span></div>

    <h1>ARSIP PROJECT</h1>
    <div class="muted">{{ $project->name }} &middot; dibuat {{ $createdAt->format('d/m/Y H:i') }} oleh {{ $createdBy ?? '-' }}</div>
    <div class="note">Salinan baca-saja saat project dihapus atau diarsipkan. Gunakan sebagai acuan bila data perlu diisi ulang secara manual.</div>

    <h2>Detail Project</h2>
    <table class="kv">
        <tr><td>Nama Project</td><td>{{ $project->name }}</td></tr>
        <tr><td>Status</td><td>{{ \App\Models\Project::STATUS_LABELS[$project->status] ?? $project->status }}</td></tr>
        <tr><td>Client</td><td>{{ $project->client ?: '-' }}@if($project->company) ({{ $project->company }})@endif</td></tr>
        <tr><td>PIC / Kontak</td><td>{{ $project->pic ?: '-' }} &middot; {{ $project->phone ?: '-' }} &middot; {{ $project->email ?: '-' }}</td></tr>
        <tr><td>Tanggal Acara</td><td>{{ $date($project->event_date) }}@if($project->event_end_date) s/d {{ $date($project->event_end_date) }}@endif</td></tr>
        <tr><td>Lokasi</td><td>{{ $project->location ?: '-' }}@if($project->address), {{ $project->address }}@endif</td></tr>
        <tr><td>Deadline</td><td>{{ $date($project->deadline) }}</td></tr>
        <tr><td>Dibuat Oleh</td><td>{{ $project->creator?->name ?? '-' }}</td></tr>
        <tr><td>Deskripsi</td><td>{{ $project->description ?: '-' }}</td></tr>
    </table>

    <h2>Ringkasan Keuangan</h2>
    <table class="kv">
        <tr><td>Estimasi Pendapatan</td><td class="r">{{ $rp($project->estimated_value) }}</td></tr>
        <tr><td>Anggaran Biaya</td><td class="r">{{ $rp($project->budget) }}</td></tr>
        <tr><td>Total Pemasukan</td><td class="r">{{ $rp($totalIncome) }}</td></tr>
        <tr><td>Total Pengeluaran</td><td class="r">{{ $rp($totalExpense) }}</td></tr>
        <tr><td>Selisih (Pemasukan - Pengeluaran)</td><td class="r">{{ $rp($totalIncome - $totalExpense) }}</td></tr>
        <tr><td>Kwitansi Aktif</td><td class="r">{{ $rp($kwitansiActive) }}</td></tr>
        <tr><td>Total Diterima dari Client</td><td class="r">{{ $rp($received) }}</td></tr>
        <tr><td>Sisa Tagihan</td><td class="r">{{ $rp($remaining) }}</td></tr>
    </table>

    @foreach(['Pemasukan' => $incomes, 'Pengeluaran' => $expenses] as $title => $rows)
        <h2>{{ $title }}</h2>
        <table>
            <thead>
                <tr>
                    <th class="c" style="width: 5%">No</th>
                    <th style="width: 10%">Tanggal</th>
                    <th style="width: 14%">Kategori</th>
                    <th style="width: 14%">Pihak</th>
                    <th style="width: 24%">Keterangan</th>
                    <th style="width: 9%">Metode</th>
                    <th style="width: 9%">Sumber</th>
                    <th class="r" style="width: 15%">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $item)
                    <tr>
                        <td class="c">{{ $loop->iteration }}</td>
                        <td>{{ $date($item->tanggal) }}</td>
                        <td>{{ $item->category ?: '-' }}</td>
                        <td>{{ $item->contact?->name ?? ($item->recipient ?: '-') }}</td>
                        <td>{{ $item->description ?: '-' }}</td>
                        <td>{{ $item->payment_method ?: '-' }}</td>
                        <td>{{ $source($item) }}</td>
                        <td class="r">{{ $rp($item->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="c muted">Tidak ada data.</td></tr>
                @endforelse
                @if($rows->isNotEmpty())
                    <tr class="total"><td colspan="7" class="r">Total {{ $title }}</td><td class="r">{{ $rp($rows->sum('amount')) }}</td></tr>
                @endif
            </tbody>
        </table>
    @endforeach

    <h2>Kwitansi</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 16%">Nomor</th>
                <th style="width: 11%">Tanggal</th>
                <th style="width: 13%">Kategori</th>
                <th style="width: 12%">Metode</th>
                <th style="width: 22%">Keterangan</th>
                <th style="width: 11%">Status</th>
                <th class="r" style="width: 15%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($kwitansis as $kwitansi)
                <tr>
                    <td>{{ $kwitansi->nomor }}</td>
                    <td>{{ $date($kwitansi->tanggal) }}</td>
                    <td>{{ $kwitansi->kategori_pembayaran ?: '-' }}</td>
                    <td>{{ $kwitansi->metode_pembayaran ?: '-' }}</td>
                    <td>{{ $kwitansi->keterangan ?: '-' }}</td>
                    <td>{{ $kwitansi->status }}</td>
                    <td class="r">{{ $rp($kwitansi->jumlah) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="c muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Pembelian Terkait</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 15%">Kode</th>
                <th style="width: 22%">Vendor</th>
                <th style="width: 14%">Status</th>
                <th style="width: 14%">Pembayaran</th>
                <th style="width: 11%">Tgl Bayar</th>
                <th class="r" style="width: 12%">Total</th>
                <th class="r" style="width: 12%">Dibayar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->code }}</td>
                    <td>{{ $purchase->vendor_name ?: '-' }}</td>
                    <td>{{ $purchase->status }}</td>
                    <td>{{ $purchase->payment_status }}</td>
                    <td>{{ $date($purchase->paid_at) }}</td>
                    <td class="r">{{ $rp($purchase->total) }}</td>
                    <td class="r">{{ $rp($purchase->paid_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="c muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Surat Jalan</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 18%">Nomor</th>
                <th style="width: 14%">Tanggal Terbit</th>
                <th style="width: 24%">Kepada</th>
                <th style="width: 30%">Keperluan</th>
                <th style="width: 14%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suratJalans as $suratJalan)
                <tr>
                    <td>{{ $suratJalan->nomor }}</td>
                    <td>{{ $date($suratJalan->tanggal_terbit) }}</td>
                    <td>{{ $suratJalan->kepada ?: '-' }}</td>
                    <td>{{ $suratJalan->keperluan ?: '-' }}</td>
                    <td>{{ $suratJalan->status }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="c muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
