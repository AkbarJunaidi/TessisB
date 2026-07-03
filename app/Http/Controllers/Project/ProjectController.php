<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ProjectRequest;
use App\Models\Project;
use App\Services\ProjectService;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Constructor.
     */
    public function __construct(
        protected ProjectService $projectService,
        protected TaskService $taskService
    ) {}

    /**
     * Menampilkan daftar project.
     */
    public function index(): View
    {
        $projects = $this->projectService->getAllPaginated(10);

        return view('project.index', compact('projects'));
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
     * Menampilkan Board Project.
     */
    public function show(Project $project): View
    {
        $groupedTasks = $this->taskService
            ->getTasksByProject($project);

        return view(
            'project.board',
            compact(
                'project',
                'groupedTasks'
            )
        );
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
}