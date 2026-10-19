<?php

namespace App\Services\Purchase;

use App\Models\Purchase;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Notifikasi alur Pembelian lewat sistem Pengumuman (kotak notifikasi + Web Push).
 * Gagal kirim tidak boleh membatalkan perubahan status, jadi dibungkus try/catch.
 * Penerima: pembuat, penyetuju (izin approval.decide), dan finance (izin purchase.pay).
 */
class PurchaseNotifier
{
    public function submitted(Purchase $purchase): void
    {
        $again = $purchase->submit_count > 1 ? " (pengajuan ulang ke-{$purchase->submit_count})" : '';

        $this->send(
            $this->usersWith('approval', 'decide'),
            'Pembelian menunggu persetujuan',
            "{$purchase->code}{$again} - {$purchase->vendor_name}, " . Money::formatRupiah($purchase->total) . '.',
            $purchase
        );
    }

    public function approved(Purchase $purchase): void
    {
        $next = $purchase->isOnline()
            ? 'Silakan buat pesanan di toko online lalu tandai Dipesan.'
            : 'Silakan hubungi vendor lalu tandai Dipesan.';

        $this->send([$purchase->created_by], 'Pembelian disetujui', "{$purchase->code} disetujui. {$next}", $purchase);

        if ($purchase->fund_status === Purchase::FUND_REQUESTED) {
            $this->send(
                $this->usersWith('purchase', 'pay'),
                'Dana perlu disiapkan',
                "{$purchase->code} ({$purchase->vendor_name}) butuh dana " . Money::formatRupiah($purchase->total) . '. Menunggu transfer dari bos.',
                $purchase
            );
        }
    }

    public function rejected(Purchase $purchase, ?string $note): void
    {
        $this->send(
            [$purchase->created_by],
            'Pembelian ditolak',
            "{$purchase->code} ditolak. " . ($note ?: 'Tanpa catatan.') . ' Ubah lalu ajukan kembali.',
            $purchase
        );
    }

    public function ordered(Purchase $purchase): void
    {
        $eta = $purchase->expected_arrival ? ', estimasi tiba ' . $purchase->expected_arrival->translatedFormat('d M Y') : '';
        $target = $purchase->isOnline() ? ($purchase->shop_name ?: 'toko online') : $purchase->vendor_name;

        $this->send(
            array_merge([$purchase->created_by], $this->usersWith('purchase', 'pay')),
            'Pembelian sudah dipesan',
            "{$purchase->code} dipesan ke {$target}{$eta}.",
            $purchase
        );
    }

    public function fundTransferred(Purchase $purchase): void
    {
        $this->send(
            $this->usersWith('purchase', 'pay'),
            'Dana siap dibayarkan',
            'Dana ' . Money::formatRupiah($purchase->fund_amount ?? $purchase->total) . " untuk {$purchase->code} ({$purchase->vendor_name}) sudah ditransfer ke finance. Silakan bayar ke vendor dan unggah bukti.",
            $purchase
        );
    }

    public function paid(Purchase $purchase): void
    {
        $this->send([$purchase->created_by], 'Pembelian sudah dibayar', "{$purchase->code} sudah dibayar ke {$purchase->vendor_name}.", $purchase);
    }

    public function received(Purchase $purchase, bool $complete = true): void
    {
        $this->send(
            [$purchase->created_by],
            $complete ? 'Barang pembelian diterima' : 'Barang pembelian diterima sebagian',
            $complete
                ? "{$purchase->code} diterima semua dan stok Inventory diperbarui."
                : "{$purchase->code} baru diterima sebagian. Sisanya masih ditunggu.",
            $purchase
        );
    }

    public function cancelled(Purchase $purchase, ?string $reason): void
    {
        $this->send(
            array_merge([$purchase->created_by], $this->usersWith('purchase', 'pay')),
            'Pembelian dibatalkan',
            "{$purchase->code} ({$purchase->vendor_name}) dibatalkan." . ($reason ? " Alasan: {$reason}" : ''),
            $purchase
        );
    }

    public function returned(Purchase $purchase, string $summary): void
    {
        $this->send(
            array_merge([$purchase->created_by], $this->usersWith('purchase', 'pay')),
            'Barang pembelian diretur',
            "{$purchase->code} ({$purchase->vendor_name}): {$summary}. Total sekarang " . Money::formatRupiah($purchase->total) . '.',
            $purchase
        );
    }

    /**
     * @return array<int, int>
     */
    private function usersWith(string $module, string $action): array
    {
        return User::where('status', 'active')->get()
            ->filter(fn (User $user) => $user->hasPermission($module, $action))
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<int, int|null>  $userIds
     */
    private function send(array $userIds, string $title, string $message, Purchase $purchase): void
    {
        try {
            $actor = Auth::user();
            $ids = collect($userIds)->filter()->unique()->reject(fn ($id) => $id === $actor?->id);

            User::where('status', 'active')->whereIn('id', $ids)->get()->each(
                fn (User $user) => $user->notify(new AnnouncementNotification(
                    title: $title,
                    message: Str::limit($message, 480),
                    sentByName: $actor?->name ?? 'Sistem',
                    url: route('purchases.show', $purchase, false),
                ))
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
