<?php

namespace App\Http\Requests\Inventory;

use App\Models\AppSetting;
use App\Models\Location;
use App\Services\Inventory\LocationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocationRequest extends FormRequest
{
    /**
     * Hanya yang punya hak mengelola master Lokasi.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('inventory', 'manage_locations') ?? false;
    }

    public function rules(): array
    {
        $location   = $this->route('location');
        $locationId = $location instanceof Location ? $location->id : $location;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                // Nama boleh sama selama jenisnya berbeda (mis. "Kantor A" kantor vs lokasi event).
                Rule::unique('locations', 'name')
                    ->where('jenis', $this->input('jenis'))
                    ->ignore($locationId),
            ],
            'jenis'     => ['required', Rule::in(array_keys(Location::JENIS))],
            'address'   => ['nullable', 'string', 'max:500'],
            'koordinat' => [
                'nullable',
                'string',
                'max:300',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (filled($value) && LocationService::parseCoordinates($value) === null) {
                        $fail('Format koordinat tidak dikenali. Contoh: -7.2575, 112.7521 (atau tempel tautan Google Maps).');
                    }
                },
            ],
            'radius_m'        => ['nullable', 'integer', 'min:10', 'max:5000'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'can_store_units' => ['nullable', 'boolean'],
            'is_default'      => ['nullable', 'boolean'],
            'is_active'       => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Nama lokasi wajib diisi.',
            'name.unique'     => 'Lokasi dengan nama dan jenis yang sama sudah ada.',
            'jenis.required'  => 'Jenis lokasi wajib dipilih.',
            'jenis.in'        => 'Jenis lokasi tidak valid.',
            'radius_m.min'    => 'Radius minimal 10 meter.',
            'radius_m.max'    => 'Radius maksimal 5000 meter.',
            'radius_m.integer' => 'Radius harus berupa angka bulat (meter).',
        ];
    }

    /**
     * Data siap simpan untuk LocationService (koordinat sudah dipecah jadi latitude/longitude).
     */
    public function payload(): array
    {
        $coords = LocationService::parseCoordinates($this->input('koordinat'));

        return [
            'name'            => trim((string) $this->input('name')),
            'jenis'           => $this->input('jenis'),
            'address'         => filled($this->input('address')) ? trim((string) $this->input('address')) : null,
            'latitude'        => $coords['lat'] ?? null,
            'longitude'       => $coords['lng'] ?? null,
            'radius_m'        => $this->filled('radius_m') ? (int) $this->input('radius_m') : AppSetting::int('location_default_radius'),
            'can_store_units' => $this->boolean('can_store_units'),
            'is_default'      => $this->boolean('is_default'),
            'is_active'       => $this->boolean('is_active'),
            'notes'           => filled($this->input('notes')) ? trim((string) $this->input('notes')) : null,
        ];
    }
}
