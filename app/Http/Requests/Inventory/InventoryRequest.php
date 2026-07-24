<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        // Izin diberikan karena rute ini sudah dilindungi oleh middleware 'auth' global
        return true;
    }

//  Aturan validasi yang diterapkan pada input form inventory.
    public function rules(): array
    {
        // Kondisi untuk membedakan validasi antara Create (POST) dan Update (PUT/PATCH)
        $inventoryId = $this->route('inventory') ? ($this->route('inventory')->id ?? $this->route('inventory')) : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => [
                'required',
                'string',
                'max:255',
                // Mengizinkan nomor seri yang sama diabaikan jika sedang melakukan proses edit data sendiri
                'unique:inventories,serial_number,' . $inventoryId
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => [
                'required',
                'string',
                // Membatasi status hanya pada pilihan yang telah ditentukan
                Rule::in(['Tersedia', 'Dipinjam', 'Perbaikan', 'Rusak', 'Hilang'])
            ],
            'brand' => ['nullable', 'string', 'max:100'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5148' // Maksimal ukuran gambar 5MB
            ],

            // Validasi Atribut Dinamis (Informasi Tambahan)
            // Dibatasi maksimal 8 baris agar tabel tetap muat 1 halaman saat dicetak ke laporan PDF
            'use_attributes' => ['nullable', 'in:1,0,true,false'],
            'attributes' => ['nullable', 'array', 'max:8'],
            'attributes.*.name' => ['nullable', 'string', 'max:40'],
            'attributes.*.value' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Kustomisasi pesan kesalahan validasi dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama barang wajib diisi.',
            'name.max' => 'Nama barang maksimal 255 karakter.',
            'serial_number.required' => 'Serial number wajib diisi.',
            'serial_number.unique' => 'Serial number ini sudah terdaftar di sistem.',
            'status.required' => 'Status barang wajib dipilih.',
            'status.in' => 'Status barang tidak valid.',
            'image.image' => 'Berkas harus berupa gambar.',
            'image.mimes' => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'image.max' => 'Ukuran gambar tidak boleh melebihi 5MB.',
            'description.max' => 'Deskripsi barang maksimal 500 karakter.',
            'brand.max' => 'Brand maksimal 100 karakter.',
            'attributes.max' => 'Informasi tambahan maksimal 8 baris agar laporan PDF tetap muat 1 halaman.',
            'attributes.*.name.max' => 'Nama informasi tambahan maksimal 40 karakter.',
            'attributes.*.value.max' => 'Nilai informasi tambahan maksimal 100 karakter.',
        ];
    }
}
