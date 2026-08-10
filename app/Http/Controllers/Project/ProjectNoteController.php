<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ProjectNoteRequest;
use App\Models\ProjectNote;
use App\Services\ProjectNoteService;
use Illuminate\Http\RedirectResponse;

class ProjectNoteController extends Controller
{
    public function __construct(
        protected ProjectNoteService $projectNoteService
    ) {}

    /**
     * Menyimpan catatan baru pada project.
     */
    public function store(ProjectNoteRequest $request): RedirectResponse
    {
        $this->projectNoteService->storeNote($request->validated());

        return redirect()->back()->with('success', 'Catatan berhasil ditambahkan.');
    }

    /**
     * Menghapus catatan (hanya pembuat catatan atau Super Admin).
     */
    public function destroy(ProjectNote $note): RedirectResponse
    {
        try {
            $this->projectNoteService->deleteNote($note);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Catatan berhasil dihapus.');
    }
}
