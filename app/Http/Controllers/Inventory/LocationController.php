<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\LocationRequest;
use App\Models\Inventory;
use App\Models\Location;
use App\Services\Inventory\LocationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Master Lokasi (kantor, gudang, lokasi event, dll) + pemindahan lokasi unit barang.
 */
class LocationController extends Controller
{
    public function __construct(
        protected LocationService $locationService
    ) {}

    /**
     * Halaman Lokasi: daftar lokasi + ringkasan unit per lokasi.
     */
    public function index(Request $request): View
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data lokasi.'
        );

        $filters   = $request->only(['search', 'jenis']);
        $locations = $this->locationService->getFiltered($filters);
        $stats     = $this->locationService->getUnitStats();

        return view('inventory.locations.index', compact('locations', 'stats', 'filters'));
    }

    /**
     * [AJAX] Autocomplete lokasi (pola sama dengan pencarian Kontak). Dipakai juga oleh
     * form lain yang butuh memilih lokasi yang sudah ada. ?q=kata&storage=1 membatasi
     * ke lokasi yang bisa menyimpan unit.
     */
    public function search(Request $request): JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'view'),
            403,
            'Anda tidak memiliki hak akses untuk melihat data lokasi.'
        );

        $locations = $this->locationService->search(
            (string) $request->query('q', ''),
            $request->boolean('storage')
        );

        return response()->json([
            'locations' => $locations->map(fn (Location $l) => $l->toFormArray())->values(),
        ]);
    }

    /**
     * [AJAX] Deteksi lokasi dari koordinat GPS perangkat.
     */
    public function detect(Request $request): JsonResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'move_location'),
            403,
            'Anda tidak memiliki hak akses untuk memindahkan lokasi unit.'
        );

        $validator = Validator::make($request->query(), [
            'lat'      => ['required', 'numeric', 'between:-90,90'],
            'lng'      => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $result = $this->locationService->detect(
            (float) $request->query('lat'),
            (float) $request->query('lng'),
            $request->filled('accuracy') ? (float) $request->query('accuracy') : null
        );

        return response()->json([
            'status'     => $result['status'],
            'location'   => isset($result['location'])
                ? ['id' => $result['location']->id, 'name' => $result['location']->name]
                : null,
            'distance_m' => $result['distance_m'] ?? null,
            'accuracy_m' => $result['accuracy_m'] ?? null,
        ]);
    }

    public function store(LocationRequest $request): RedirectResponse
    {
        try {
            $location = $this->locationService->create($request->payload());

            return redirect()
                ->route('inventory.locations.index')
                ->with('success', "Lokasi \"{$location->name}\" berhasil ditambahkan.");
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(LocationRequest $request, Location $location): RedirectResponse
    {
        try {
            $this->locationService->update($location, $request->payload());

            return redirect()
                ->route('inventory.locations.index')
                ->with('success', "Lokasi \"{$location->name}\" berhasil diperbarui.");
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(Location $location): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'manage_locations'),
            403,
            'Anda tidak memiliki hak akses untuk menghapus lokasi.'
        );

        try {
            $this->locationService->delete($location);

            return redirect()
                ->route('inventory.locations.index')
                ->with('success', "Lokasi \"{$location->name}\" berhasil dihapus.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pindahkan beberapa unit sebuah barang ke lokasi lain (dari halaman Detail Inventory).
     */
    public function moveUnits(Request $request, Inventory $inventory): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('inventory', 'move_location'),
            403,
            'Anda tidak memiliki hak akses untuk memindahkan lokasi unit.'
        );

        $validator = Validator::make($request->all(), [
            'unit_ids'           => ['required', 'array', 'min:1'],
            'unit_ids.*'         => ['integer'],
            'target_location_id' => ['required', 'integer'],
            'make_home'          => ['nullable', 'boolean'],
            'lat'                => ['nullable', 'numeric', 'between:-90,90'],
            'lng'                => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy'           => ['nullable', 'numeric', 'min:0'],
        ], [
            'unit_ids.required'           => 'Pilih minimal satu unit.',
            'target_location_id.required' => 'Pilih lokasi tujuan.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            $moved = $this->locationService->moveUnits(
                $inventory,
                array_map('intval', $request->input('unit_ids')),
                (int) $request->input('target_location_id'),
                $request->boolean('make_home'),
                $request->filled('lat') ? (float) $request->input('lat') : null,
                $request->filled('lng') ? (float) $request->input('lng') : null,
                $request->filled('accuracy') ? (float) $request->input('accuracy') : null
            );

            $targetName = Location::find((int) $request->input('target_location_id'))?->name ?? 'lokasi tujuan';

            return redirect()
                ->route('inventory.show', $inventory)
                ->with('success', "{$moved} unit dipindahkan ke {$targetName}.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
