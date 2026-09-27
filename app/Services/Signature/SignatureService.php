<?php

namespace App\Services\Signature;

use App\Models\Signature;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class SignatureService
{
    protected const DISK = 'public';
    protected const DIR  = 'assets/signatures';

    /**
     * Simpan tanda tangan baru - bisa dari file upload (UploadedFile) ATAU
     * dari kanvas (data URI base64 "data:image/png;base64,...."), tinggal
     * kirim salah satu lewat parameter yang sesuai.
     */
    public function store(User $user, string $label, ?UploadedFile $file, ?string $canvasDataUrl): Signature
    {
        $path = $file
            ? $file->store(self::DIR, self::DISK)
            : $this->storeCanvasDataUrl($canvasDataUrl);

        $isFirstSignature = $user->signatures()->count() === 0;

        return Signature::create([
            'user_id'    => $user->id,
            'label'      => $label,
            'file_path'  => $path,
            'is_default' => $isFirstSignature,
        ]);
    }

    protected function storeCanvasDataUrl(?string $dataUrl): string
    {
        if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/png;base64,')) {
            throw new Exception('Data gambar tanda tangan tidak valid.');
        }

        $binary = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')));
        $path = self::DIR . '/' . Str::uuid() . '.png';

        Storage::disk(self::DISK)->put($path, $binary);

        return $path;
    }

    public function setDefault(Signature $signature): void
    {
        Signature::where('user_id', $signature->user_id)
            ->where('id', '!=', $signature->id)
            ->update(['is_default' => false]);

        $signature->update(['is_default' => true]);
    }

    public function delete(Signature $signature): void
    {
        if (Storage::disk(self::DISK)->exists($signature->file_path)) {
            Storage::disk(self::DISK)->delete($signature->file_path);
        }

        $wasDefault = $signature->is_default;
        $userId = $signature->user_id;

        $signature->delete();

        // Kalau yang dihapus itu default, jadikan salah satu sisanya default
        // biar user tidak "kehilangan" default tanpa sadar.
        if ($wasDefault) {
            $next = Signature::where('user_id', $userId)->first();
            $next?->update(['is_default' => true]);
        }
    }
}
