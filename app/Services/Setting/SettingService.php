<?php

namespace App\Services\Setting;

use App\Models\AppSetting;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Pengaturan Pemilik: profil perusahaan, gambar dokumen, kategori project, status barang, dan aturan operasional.
 */
class SettingService
{
    public const TEXT_KEYS = [
        'company_name', 'company_tagline', 'company_address', 'company_phone', 'company_whatsapp',
        'company_footer_tagline', 'company_footer_contact', 'company_website', 'company_instagram', 'company_tiktok',
        'servis_segera_hari', 'repair_warn_percent', 'location_default_radius',
        'upload_max_mb', 'upload_allowed_extensions',
    ];

    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /** @return array<string, mixed> */
    public function formValues(): array
    {
        $values = [];

        foreach (self::TEXT_KEYS as $key) {
            $values[$key] = AppSetting::get($key);
        }

        $values['project_categories'] = AppSetting::projectCategories();
        $values['inventory_statuses'] = AppSetting::inventoryStatuses();

        return $values;
    }

    /** @return array<string, array{url: string, custom: bool}> */
    public function imageInfo(): array
    {
        $info = [];

        foreach (array_keys(config('app_settings.images')) as $key) {
            $info[$key] = [
                'url'    => AppSetting::imageUrl($key),
                'custom' => AppSetting::hasCustomImage($key),
            ];
        }

        return $info;
    }

    /**
     * @param array<string, mixed>        $data
     * @param array<string, UploadedFile> $files       key gambar => file baru
     * @param array<int, string>          $resetImages key gambar yang dikembalikan ke bawaan
     */
    public function update(array $data, array $files, array $resetImages): void
    {
        DB::transaction(function () use ($data, $files, $resetImages) {
            foreach (self::TEXT_KEYS as $key) {
                $this->put($key, (string) ($data[$key] ?? ''));
            }

            $this->put('project_categories', json_encode(array_values($data['project_categories']), JSON_UNESCAPED_UNICODE));
            $this->put('inventory_statuses', json_encode(array_values($data['inventory_statuses'] ?? []), JSON_UNESCAPED_UNICODE));

            foreach (array_keys(config('app_settings.images')) as $key) {
                if (isset($files[$key])) {
                    $this->replaceImage($key, $files[$key]);
                } elseif (in_array($key, $resetImages, true)) {
                    $this->removeImage($key);
                }
            }
        });

        AppSetting::flush();

        $this->activityLogService->log(Auth::id(), 'Pengaturan', 'Update Pengaturan');
    }

    private function put(string $key, string $value): void
    {
        AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    private function replaceImage(string $key, UploadedFile $file): void
    {
        $old = AppSetting::get('image_' . $key);

        $path = $file->storeAs('settings', $key . '_' . time() . '.' . strtolower($file->getClientOriginalExtension()), 'public');
        $this->put('image_' . $key, $path);

        if ($old !== '') {
            Storage::disk('public')->delete($old);
        }
    }

    private function removeImage(string $key): void
    {
        $old = AppSetting::get('image_' . $key);

        if ($old !== '') {
            Storage::disk('public')->delete($old);
        }

        AppSetting::where('key', 'image_' . $key)->delete();
    }
}
