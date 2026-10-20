<?php

namespace App\Support;

/**
 * Data langkah untuk komponen stepper (resources/views/partials/stepper.blade.php).
 */
class Stepper
{
    /**
     * @param  array<int, string>  $labels  nama tiap langkah, berurutan
     * @param  int  $at  indeks langkah yang sedang berjalan
     * @param  bool  $finished  semua langkah selesai
     * @param  bool  $stopped  langkah berjalan terhenti (ditolak atau dibatalkan)
     * @param  array<int, ?string>  $hints  keterangan kecil per langkah (mis. tanggal)
     * @return array<int, array{label: string, state: string, hint: ?string}>
     */
    public static function steps(array $labels, int $at, bool $finished = false, bool $stopped = false, array $hints = []): array
    {
        $steps = [];

        foreach (array_values($labels) as $i => $label) {
            $state = $finished || $i < $at ? 'done' : ($i === $at ? ($stopped ? 'stop' : 'current') : 'todo');

            $steps[] = [
                'label' => $label,
                'state' => $state,
                'hint'  => $state === 'todo' ? null : ($hints[$i] ?? null),
            ];
        }

        return $steps;
    }
}
