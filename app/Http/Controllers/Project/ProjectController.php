<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ProjectCrewRequest;
use App\Http\Requests\Project\ProjectRequest;
use App\Models\Project;
use App\Services\ProjectCrewService;
use App\Services\ProjectService;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Constructor.
     */
    public function __construct(
        protected ProjectService $projectService,
        protected ProjectCrewService $projectCrewService,
        protected TaskService $taskService
    ) {}

    /**
     * Menampilkan daftar project: stat card, kalender, dan tabel dengan filter.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'pic', 'month', 'date']);

        $projects = $this->projectService->getAllPaginated($filters, 10);
        $stats    = $this->projectService->getStats();

        $calendarMonth = (int) ($request->query('cal_month', now()->month));
        $calendarYear  = (int) ($request->query('cal_year', now()->year));
        $calendarData  = $this->projectService->getCalendarData($calendarMonth, $calendarYear);

        $pics = $this->projectService->getDistinctPics();

        $allProjectsForPicker = Project::select('id', 'name')->orderBy('name')->get();

        return view('project.index', compact(
            'projects', 'stats', 'calendarData', 'calendarMonth', 'calendarYear', 'pics', 'filters', 'allProjectsForPicker'
        ));
    }

    /**
     * Menampilkan halaman tambah project.
     */
    public function create(): View
    {
        return view('project.create');
    }

    /**
     * Menyimpan project baru.
     */
    public function store(ProjectRequest $request): RedirectResponse
    {
        $this->projectService->createProject(
            $request->validated()
        );

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan Detail Project: header, info kunci, crew, aksi cepat,
     * tab Overview/Kanban/Barang/Dokumen/Catatan.
     */
    public function show(Project $project): View
    {
        $groupedTasks = $this->taskService->getTasksByProject($project);

        $project->load([
            'creator',
            'crews',
            'notes.user',
            'suratJalans.items.inventory',
            'folder.files.user',
        ]);

        $allUsers = \App\Models\User::where('status', 'active')->orderBy('name')->get();
        $allFolders = \App\Models\Folder::orderBy('name')->get();

        return view(
            'project.show',
            compact('project', 'groupedTasks', 'allUsers', 'allFolders')
        );
    }

    /**
     * Menampilkan halaman edit project.
     */
    public function edit(Project $project): View
    {
        return view('project.edit', compact('project'));
    }

    /**
     * Memperbarui data project.
     */
    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->projectService->updateProject(
            $project,
            $request->validated()
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project berhasil diperbarui.');
    }

    /**
     * Menghapus project.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->projectService->deleteProject($project);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project berhasil dihapus.');
    }

    /**
     * Mengubah status project (dipakai oleh stepper status di Detail Project).
     */
    public function updateStatus(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:Draft,Scheduled,Confirmed,In Progress,On Review,Done'],
        ]);

        $project->update(['status' => $request->input('status')]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Status project berhasil diperbarui.');
    }

    /**
     * Menambahkan list/kolom Kanban baru (fitur "Add List").
     */
    public function storeList(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'label' => ['required', 'string', 'max:50'],
        ]);

        $colors = ['secondary', 'primary', 'warning', 'success', 'info', 'danger', 'dark'];
        $color = $colors[count($project->getBoardLists()) % count($colors)];

        $project->addBoardList($request->input('label'), $color);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'List baru berhasil ditambahkan ke board.');
    }

    /**
     * Memperbarui Crew/Tim Project (Super Admin & Admin).
     */
    public function updateCrew(ProjectCrewRequest $request, Project $project): RedirectResponse
    {
        $this->projectCrewService->syncCrew(
            $project,
            $request->validated()['crew'] ?? []
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Crew project berhasil diperbarui.');
    }
}
