<div class="page-bundle">

    <table class="header-table">
        <tr>
            <td style="width: 30%;">
                {{-- foto logo perusahaan --}}
                @php
                    $logoPath = public_path('image/logoAP.png');
                    $logoBase64 = '';
                    if (file_exists($logoPath)) {
                        $logoType = mime_content_type($logoPath);
                        $logoBase64 = 'data:' . $logoType . ';base64,' . base64_encode(file_get_contents($logoPath));
                    }
                @endphp


                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo" style="width: 180px; height: auto;">
                @else
                    <p class="brand-name">{{ config('app.name', 'TESSIS') }}</p>
                @endif
                {{-- <p class="brand-sub">CV. Arindra Production</p> --}}

            </td>
            <td class="header-title" style="width: 40%;">
                <p class="report-title">INVENTORY REPORT</p>
                {{-- <p class="report-subtitle">LAPORAN INFORMASI ASET</p> --}}
            </td>
            <td class="header-meta" style="width: 30%;">
                Generated :<br>
                {{ $exportDate }}
            </td>
        </tr>
    </table>
    <hr class="header-rule">

    <!-- Bagian 1: Foto Barang & Informasi Identitas -->
    <table class="section-table">
        <tr>
            <td class="photo-cell">
                @if($inventory->image && file_exists(storage_path('app/public/' . $inventory->image)))
                    <img src="{{ storage_path('app/public/' . $inventory->image) }}" class="inventory-photo">
                @else
                    <div class="no-photo-placeholder">Tidak ada foto aset barang</div>
                @endif
            </td>
            <td class="identity-cell">
                <table class="identity-table">
                    <tr>
                        <td class="identity-label">Nama Barang</td>
                        <td class="identity-value">{{ $inventory->name }}</td>
                    </tr>
                    <tr>
                        <td class="identity-label">Serial Number</td>
                        <td class="identity-value">{{ $inventory->serial_number }}</td>
                    </tr>
                    <tr>
                        <td class="identity-label">Status</td>
                        <td>
                            @php
                                $statusSlug = match($inventory->status) {
                                    'Dipinjam'  => 'dipinjam',
                                    'Perbaikan' => 'perbaikan',
                                    'Rusak'     => 'rusak',
                                    'Hilang'    => 'hilang',
                                    default     => 'tersedia',
                                };
                            @endphp
                            <span class="status-badge status-{{ $statusSlug }}">{{ $inventory->status ?? 'Tersedia' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="identity-label">Brand</td>
                        <td class="identity-value">{{ $inventory->brand ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="identity-label">Tanggal Input</td>
                        <td class="identity-value">{{ $inventory->created_at ? $inventory->created_at->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="identity-label">Terakhir Update</td>
                        <td class="identity-value">{{ $inventory->updated_at ? $inventory->updated_at->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Bagian 2: Deskripsi Barang -->
    <div class="box-section">
        <p class="box-title">Deskripsi Barang</p>
        @if(!empty($inventory->description))
            <div class="box-body">{{ $inventory->description }}</div>
        @else
            <div class="box-body-empty">Belum ada deskripsi.</div>
        @endif
    </div>

    <!-- Bagian 3: Informasi Tambahan (jika ada) & QR Code -->
    <table class="bottom-table">
        <tr>
            <td style="vertical-align: top;">
                @if($inventory->attributes && $inventory->attributes->count() > 0)
                    <div class="box-section" style="margin-bottom: 0;">
                        <p class="box-title">Informasi Tambahan</p>
                        <table class="attr-table">
                            <tr>
                                <th>Nama Informasi</th>
                                <th>Nilai</th>
                            </tr>
                            @foreach($inventory->attributes as $attr)
                                <tr>
                                    <td>{{ $attr->attribute_name }}</td>
                                    <td>{{ $attr->attribute_value }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </td>
            <td class="qr-cell">
                <div class="box-section" style="margin-bottom: 0;">
                    <p class="box-title">QR Code</p>
                    @if($inventory->qr_code && file_exists(storage_path('app/public/' . $inventory->qr_code)))
                        <img src="{{ storage_path('app/public/' . $inventory->qr_code) }}" class="qr-code-img">
                        <div class="qr-caption" style="font-weight: bold; color: #333333;">{{ $inventory->serial_number }}</div>
                        {{-- <div class="qr-caption">Scan QR code di atas<br>untuk melihat detail aset<br>secara lengkap.</div> --}}
                    @else
                        <div class="no-photo-placeholder" style="height: 120px; line-height: 120px;">QR belum tersedia</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-container">
        <table class="footer-table">
            <tr>
                <td>
                    {{-- Dicetak oleh<br> --}}
                    {{ auth()->user()->name ?? 'Admin' }}<br>
                    {{ $exportDate }}
                </td>
                <td class="footer-right">
                    CV. Arindra Production<br>

                </td>
            </tr>
        </table>
    </div>

</div>
