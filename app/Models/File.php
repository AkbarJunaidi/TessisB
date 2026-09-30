<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class File extends Model
{
    use SoftDeletes;

    /**
     * Nama tabel yang dikelola di dalam MySQL database.
     */
    protected $table = 'files';

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'folder_id',
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'deleted_by',
    ];

    /**
     * Relasi ke Folder tempat file bernaung (Belongs To).
     * Dapat mengembalikan NULL jika file berada di root 'My Files' pribadi.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    /**
     * Relasi ke User pemilik / pengunggah file (Belongs To).
     * Sinkronisasi mutlak untuk mengatasi RelationNotFoundException di View & Controller.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi Alias ke User pemilik file (Belongs To).
     * Dipertahankan sebagai back-up opsional business logic layer.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Aksesor opsional untuk mengubah ukuran byte menjadi format yang mudah dibaca (Human Readable).
     * Sangat berguna saat presentasi demo UI di hadapan penguji!
     */
    public function getReadableSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Relasi BelongsTo: User yang melakukan penghapusan (untuk fitur Trash).
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Folder penyimpanan file yang DIUNGGAH manual lewat Integrasi Data
     * (lihat FileService::uploadFile). File di luar folder ini adalah hasil
     * generate sistem (mis. PDF Surat Jalan di "surat-jalan/", Kwitansi di "kwitansi/").
     */
    public const UPLOAD_DIR = 'uploads/data_integration';

    /**
     * Asal file: 'upload' (diunggah user) atau 'generate' (dibuat sistem).
     */
    public function getSourceAttribute(): string
    {
        return Str::startsWith((string) $this->file_path, self::UPLOAD_DIR . '/')
            ? 'upload'
            : 'generate';
    }

    /**
     * Label asal file untuk ditampilkan, mis. "Diunggah" / "Digenerate - Surat Jalan".
     */
    public function getSourceLabelAttribute(): string
    {
        if ($this->source === 'upload') {
            return 'Diunggah';
        }

        $dir = strtok((string) $this->file_path, '/');

        return match ($dir) {
            'surat-jalan' => 'Digenerate - Surat Jalan',
            'kwitansi'    => 'Digenerate - Kwitansi',
            default       => 'Digenerate',
        };
    }

    /**
     * Kelas ikon Bootstrap Icons + warna berdasarkan jenis file (gaya Drive).
     */
    public function getIconClassAttribute(): string
    {
        return match ($this->locked_extension) {
            'pdf'                          => 'bi-file-earmark-pdf-fill text-danger',
            'xls', 'xlsx', 'csv'           => 'bi-file-earmark-spreadsheet-fill text-success',
            'doc', 'docx'                  => 'bi-file-earmark-word-fill text-primary',
            'ppt', 'pptx'                  => 'bi-file-earmark-slides-fill text-warning',
            'jpg', 'jpeg', 'png', 'gif',
            'webp', 'bmp', 'svg'           => 'bi-file-earmark-image-fill text-info',
            'zip', 'rar', '7z'             => 'bi-file-earmark-zip-fill text-secondary',
            'txt', 'log', 'md', 'json',
            'xml'                          => 'bi-file-earmark-text-fill text-secondary',
            default                        => 'bi-file-earmark-fill text-secondary',
        };
    }

    /**
     * Ekstensi (dari file yang benar-benar tersimpan di disk) yang boleh
     * dipratinjau langsung di browser, beserta MIME yang DIKIRIM server.
     * MIME sengaja ditentukan dari ekstensi ini, bukan dari isi file, dan
     * dikirim bersama header nosniff - jadi file yang menyamar tidak akan
     * dieksekusi browser. HTML/SVG sengaja TIDAK ada di daftar (bisa memuat
     * script); Word/Excel juga belum (browser tidak bisa merendernya).
     */
    public const PREVIEW_MIME = [
        'pdf'  => ['pdf',   'application/pdf'],
        'jpg'  => ['image', 'image/jpeg'],
        'jpeg' => ['image', 'image/jpeg'],
        'png'  => ['image', 'image/png'],
        'gif'  => ['image', 'image/gif'],
        'webp' => ['image', 'image/webp'],
        'bmp'  => ['image', 'image/bmp'],
        'txt'  => ['text',  'text/plain; charset=UTF-8'],
        'csv'  => ['text',  'text/plain; charset=UTF-8'],
        'log'  => ['text',  'text/plain; charset=UTF-8'],
        'md'   => ['text',  'text/plain; charset=UTF-8'],
        'json' => ['text',  'text/plain; charset=UTF-8'],
        'xml'  => ['text',  'text/plain; charset=UTF-8'],
    ];

    /**
     * File "pribadi" = tidak berada di folder mana pun (diunggah lewat My Files).
     * Begitu dipindah ke folder, file menjadi ruang bersama.
     */
    public function isPrivate(): bool
    {
        return is_null($this->folder_id);
    }

    /**
     * Ekstensi asli file di storage (huruf kecil, tanpa titik).
     */
    private function storedExtension(): string
    {
        return strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION));
    }

    /**
     * Jenis preview: 'pdf' | 'image' | 'text' | null (tidak bisa dipratinjau).
     */
    public function getPreviewTypeAttribute(): ?string
    {
        return self::PREVIEW_MIME[$this->storedExtension()][0] ?? null;
    }

    /**
     * MIME yang dipakai saat menyajikan preview (null jika tidak didukung).
     */
    public function getPreviewMimeAttribute(): ?string
    {
        return self::PREVIEW_MIME[$this->storedExtension()][1] ?? null;
    }

    /**
     * Ekstensi yang dikunci saat rename (dari file_type, huruf kecil, tanpa titik).
     */
    public function getLockedExtensionAttribute(): string
    {
        return strtolower(ltrim((string) $this->file_type, '.'));
    }

    /**
     * Nama tanpa ekstensi - dipakai untuk mengisi kolom input rename,
     * supaya ekstensi tidak ikut terhapus tanpa sengaja.
     */
    public function getBaseNameAttribute(): string
    {
        $ext = $this->locked_extension;

        if ($ext !== '' && Str::endsWith(Str::lower($this->file_name), '.' . $ext)) {
            return mb_substr($this->file_name, 0, -(strlen($ext) + 1));
        }

        return (string) $this->file_name;
    }

    /**
     * Pastikan nama baru selalu berakhiran ekstensi asli file.
     * "laporan" -> "laporan.docx"; "laporan.docx" tetap "laporan.docx".
     */
    public function applyLockedExtension(string $newName): string
    {
        $newName = trim($newName);
        $ext     = $this->locked_extension;

        if ($ext === '' || Str::endsWith(Str::lower($newName), '.' . $ext)) {
            return mb_substr($newName, 0, 255);
        }

        // sisakan ruang untuk ekstensi agar total tetap muat di 255 karakter
        return mb_substr($newName, 0, 255 - strlen($ext) - 1) . '.' . $ext;
    }
}
