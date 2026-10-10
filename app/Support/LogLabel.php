<?php

namespace App\Support;

/**
 * Label Indonesia untuk modul/aksi log aktivitas. Data di database tidak diubah
 * (log lama tetap bisa difilter); hanya tampilan yang diterjemahkan.
 */
class LogLabel
{
    private const MODULES = [
        'Tracking Progress' => 'Progres Project',
        'Inventory'         => 'Inventaris',
        'User Management'   => 'Kelola User',
        'Authentication'    => 'Autentikasi',
        'Activity Log'      => 'Log Aktivitas',
        'Trash'             => 'Sampah',
    ];

    private const VERBS = [
        'Create '   => 'Buat ',
        'Update '   => 'Perbarui ',
        'Delete '   => 'Hapus ',
        'Upload '   => 'Unggah ',
        'Download ' => 'Unduh ',
        'Unlock '   => 'Buka Kunci ',
        'Lock '     => 'Kunci ',
        'Rename '   => 'Ganti Nama ',
        'Move '     => 'Pindah ',
        'Restore '  => 'Pulihkan ',
        'Change '   => 'Ubah ',
        'Generate ' => 'Buat ',
    ];

    private const NOUNS = [
        'Private Folder' => 'Folder Pribadi',
        'Inventory'      => 'Inventaris',
        'Contact'        => 'Kontak',
        'Password Reset Requested' => 'Permintaan Reset Password',
    ];

    private const EXACT = [
        'Create'  => 'Buat',  'Created' => 'Dibuat',
        'Update'  => 'Perbarui', 'Updated' => 'Diperbarui',
        'Delete'  => 'Hapus', 'Deleted' => 'Dihapus',
        'Password Reset Requested' => 'Permintaan Reset Password',
        'Update Task Status' => 'Perbarui Status Task',
    ];

    public static function module(?string $module): string
    {
        $module = class_basename((string) $module);

        return self::MODULES[$module] ?? $module;
    }

    public static function action(?string $action): string
    {
        $action = (string) $action;

        if (isset(self::EXACT[$action])) {
            return self::EXACT[$action];
        }

        foreach (self::VERBS as $en => $id) {
            if (str_starts_with($action, $en)) {
                $rest = substr($action, strlen($en));

                return $id . strtr($rest, self::NOUNS);
            }
        }

        return ucfirst($action);
    }
}
