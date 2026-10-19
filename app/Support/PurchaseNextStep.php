<?php

namespace App\Support;

use App\Models\Purchase;
use App\Models\User;

/**
 * Teks panel "Langkah berikutnya" dan penanda tahap di detail Pembelian: satu kalimat untuk
 * jalur barang dan satu untuk jalur uang, disesuaikan dengan status dan izin pengguna.
 */
class PurchaseNextStep
{
    /**
     * @return array{goods: ?string, money: ?string}
     */
    public static function for(Purchase $purchase, User $user, bool $canDecide): array
    {
        return [
            'goods' => self::goods($purchase, $canDecide),
            'money' => self::money($purchase, $user),
        ];
    }

    /**
     * Penanda tahap per jalur. state: done (selesai), current (sedang di sini), todo (belum), stop (berhenti).
     *
     * @return array{goods: array<int, array{label: string, state: string, hint: ?string}>, money: array<int, array{label: string, state: string, hint: ?string}>, cancelled: bool}
     */
    public static function stages(Purchase $purchase): array
    {
        return [
            'goods'     => self::goodsStages($purchase),
            'money'     => self::moneyStages($purchase),
            'cancelled' => $purchase->status === Purchase::STATUS_CANCELLED,
        ];
    }

    private static function goodsStages(Purchase $purchase): array
    {
        $labels = ['Dibuat', 'Diajukan', 'Disetujui', 'Dipesan', 'Diterima'];
        $hints  = [3 => 'boleh dilewati'];

        $current = match ($purchase->status) {
            Purchase::STATUS_DRAFT     => 0,
            Purchase::STATUS_SUBMITTED => 1,
            Purchase::STATUS_REJECTED  => 1,
            Purchase::STATUS_APPROVED  => 2,
            Purchase::STATUS_ORDERED   => 3,
            Purchase::STATUS_PARTIAL   => 4,
            Purchase::STATUS_RECEIVED  => 5,
            default                    => -1,
        };

        $stages = [];
        foreach ($labels as $i => $label) {
            $state = $current < 0 ? 'todo' : ($i < $current ? 'done' : ($i === $current ? 'current' : 'todo'));
            $hint = $hints[$i] ?? null;

            if ($purchase->status === Purchase::STATUS_REJECTED && $i === 1) {
                $state = 'stop';
                $label = 'Ditolak';
                $hint = 'ubah lalu ajukan lagi';
            }

            if ($purchase->status === Purchase::STATUS_PARTIAL && $i === 4) {
                $purchase->loadMissing('items');
                $hint = $purchase->items->sum('qty_received') . ' dari ' . $purchase->items->sum('qty') . ' barang';
                $label = 'Diterima sebagian';
            }

            // Petunjuk "boleh dilewati" tidak relevan lagi setelah tahap itu selesai.
            if ($state === 'done') {
                $hint = null;
            }

            $stages[] = ['label' => $label, 'state' => $state, 'hint' => $hint];
        }

        return $stages;
    }

    private static function moneyStages(Purchase $purchase): array
    {
        $paid = $purchase->payment_status === Purchase::PAYMENT_PAID;
        $active = in_array($purchase->status, [Purchase::STATUS_APPROVED, Purchase::STATUS_ORDERED, Purchase::STATUS_PARTIAL, Purchase::STATUS_RECEIVED], true);
        $viaFinance = $purchase->payment_timing === Purchase::TIMING_UPFRONT || $purchase->fund_status !== null;

        if ($viaFinance) {
            $position = match (true) {
                $paid                                                   => 3,
                $purchase->fund_status === Purchase::FUND_AT_FINANCE    => 2,
                $purchase->fund_status === Purchase::FUND_REQUESTED     => 1,
                $active                                                 => 0,
                default                                                 => -1,
            };

            $labels = ['Dana diminta', 'Dana di finance', 'Dibayar'];
        } else {
            $position = match (true) {
                $paid   => 2,
                $active => 0,
                default => -1,
            };

            $first = $purchase->payment_timing === Purchase::TIMING_TERM ? 'Tempo' : 'Bayar saat terima';
            $labels = [$first, 'Dibayar'];
        }

        $stages = [];
        foreach ($labels as $i => $label) {
            $state = $position < 0 ? 'todo' : ($i < $position ? 'done' : ($i === $position ? 'current' : 'todo'));
            $hint = null;

            if ($viaFinance && $i === 0 && $position >= 1) {
                $state = 'done';
            }

            if (!$viaFinance && $i === 0 && $purchase->payment_timing === Purchase::TIMING_TERM && $purchase->due_date) {
                $hint = 'jatuh tempo ' . $purchase->due_date->translatedFormat('d M Y');
            }

            $stages[] = ['label' => $label, 'state' => $state, 'hint' => $hint];
        }

        return $stages;
    }

    private static function goods(Purchase $purchase, bool $canDecide): ?string
    {
        return match ($purchase->status) {
            Purchase::STATUS_DRAFT     => 'Lengkapi item lalu ajukan ke Approval.',
            Purchase::STATUS_REJECTED  => "Perbaiki sesuai catatan penolakan lalu ajukan lagi. Kode {$purchase->code} tetap sama.",
            Purchase::STATUS_SUBMITTED => $canDecide ? 'Menunggu keputusan Anda: setujui atau tolak.' : 'Menunggu keputusan penyetuju.',
            Purchase::STATUS_APPROVED  => $purchase->isOnline()
                ? 'Buat pesanan di toko online, lalu tandai Dipesan (nomor pesanan wajib diisi).'
                : 'Hubungi vendor lewat tombol WhatsApp, lalu tandai Dipesan.',
            Purchase::STATUS_ORDERED   => 'Pesanan sudah dibuat'
                . ($purchase->expected_arrival ? ', estimasi tiba ' . $purchase->expected_arrival->translatedFormat('d M Y') : '')
                . '. Tandai Diterima saat barang tiba.',
            Purchase::STATUS_PARTIAL   => 'Sebagian barang sudah masuk. Terima sisanya saat tiba, atau retur bila ada yang bermasalah.',
            Purchase::STATUS_RECEIVED  => 'Barang sudah diterima dan stok Inventory diperbarui.',
            Purchase::STATUS_CANCELLED => 'Pembelian dibatalkan.',
            default                    => null,
        };
    }

    private static function money(Purchase $purchase, User $user): ?string
    {
        if ($purchase->payment_status === Purchase::PAYMENT_PAID) {
            return 'Pembayaran selesai.';
        }

        if (!$purchase->canPay()) {
            return null;
        }

        $amount = Money::formatRupiah($purchase->fund_amount ?? $purchase->total);

        if ($purchase->fund_status === Purchase::FUND_AT_FINANCE) {
            return $user->hasPermission('purchase', 'pay')
                ? "Dana {$amount} sudah di finance. Bayar ke vendor lalu unggah bukti."
                : "Dana {$amount} sudah di finance, menunggu pembayaran ke vendor.";
        }

        if ($purchase->fund_status === Purchase::FUND_REQUESTED) {
            return $user->hasPermission('approval', 'decide')
                ? "Dana {$amount} perlu disiapkan: catat transfer ke finance, atau bayar langsung."
                : "Dana {$amount} diminta, menunggu transfer dari bos.";
        }

        return match ($purchase->payment_timing) {
            Purchase::TIMING_UPFRONT => 'Pembayaran di muka: catat pembayaran ke vendor.',
            Purchase::TIMING_TERM    => self::termText($purchase),
            default                  => 'Bayar saat barang diterima.',
        };
    }

    private static function termText(Purchase $purchase): string
    {
        if (!$purchase->due_date) {
            return 'Pembayaran tempo.';
        }

        $date = $purchase->due_date->translatedFormat('d M Y');
        $days = $purchase->daysToDue();

        return match (true) {
            $days < 0   => 'Terlambat ' . abs($days) . " hari dari jatuh tempo {$date}. Segera bayar ke vendor.",
            $days === 0 => "Jatuh tempo hari ini ({$date}). Segera bayar ke vendor.",
            $days <= Purchase::DUE_SOON_DAYS => "Segera jatuh tempo: {$date} ({$days} hari lagi).",
            default     => "Tempo, jatuh tempo {$date}.",
        };
    }
}
