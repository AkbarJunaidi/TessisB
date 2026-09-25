<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Services\Search\GlobalSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function __construct(protected GlobalSearchService $searchService)
    {
    }

    /**
     * Hasil pencarian global (Project, Inventory, Kontak, Surat Jalan).
     * Tidak butuh permission modul tersendiri - tiap kategori di dalamnya
     * sudah digating permission modulnya masing-masing di service, jadi
     * user tanpa permission apa pun akan melihat halaman kosong ("tidak
     * ada hasil"), bukan error 403.
     */
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        $results = $keyword !== ''
            ? $this->searchService->search($keyword, Auth::user())
            : [];

        return view('search.index', [
            'keyword' => $keyword,
            'results' => $results,
        ]);
    }

    /**
     * Endpoint JSON untuk dropdown saran (typeahead) di navbar - dipanggil
     * lewat fetch() saat user mengetik, sama pola dengan
     * ContactController::search() yang dipakai autocomplete Client di
     * form Project.
     */
    public function suggest(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('q', ''));

        $results = $keyword !== ''
            ? $this->searchService->suggestions($keyword, Auth::user())
            : [];

        return response()->json(['results' => $results]);
    }
}
