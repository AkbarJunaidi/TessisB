<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ProjectNoteRequest;
use App\Models\ProjectNote;
use App\Services\Project\ProjectNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectNoteController extends Controller
{
    use RespondsToAjax;

    public function __construct(
        protected ProjectNoteService $projectNoteService
    ) {}

    /**
     * Menyimpan catatan baru pada project.
     */
    public function store(ProjectNoteRequest $request): RedirectResponse|JsonResponse
    {
        $note = $this->projectNoteService->storeNote($request->validated());

        return $this->done($request, 'Catatan berhasil ditambahkan.', [
            'html' => view('project.partials.note-item', ['note' => $note->load('user')])->render(),
        ]);
    }

    /**
     * Menghapus catatan (hanya pembuat catatan atau Super Admin).
     */
    public function destroy(Request $request, ProjectNote $note): RedirectResponse|JsonResponse
    {
        try {
            $this->projectNoteService->deleteNote($note);
        } catch (\Exception $e) {
            return $this->failed($request, $e->getMessage());
        }

        return $this->done($request, 'Catatan berhasil dihapus.');
    }
}
