<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\TaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Service Task.
     */
    protected TaskService $taskService;

    /**
     * Constructor.
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Menampilkan form tambah task baru.
     */
    public function create(Request $request): View
    {
        $project = Project::findOrFail($request->get('project_id'));

        $users = User::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('task.create', compact('project', 'users'));
    }

    /**
     * Menyimpan task baru.
     */
    public function store(TaskRequest $request): RedirectResponse
    {
        $task = $this->taskService->createTask(
            $request->validated()
        );

        return redirect()
            ->route('projects.show', $task->project_id)
            ->with('success', 'Task berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail task.
     */
    public function show(int $id): View
    {
        $task = $this->taskService->findTaskById($id);

        return view('task.show', compact('task'));
    }

    /**
     * Menampilkan form edit task.
     */
    public function edit(Task $task): View
    {
        $users = User::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('task.edit', compact('task', 'users'));
    }

    /**
     * Memperbarui data task.
     */
    public function update(
        TaskRequest $request,
        Task $task
    ): RedirectResponse {

        $this->taskService->updateTask(
            $task,
            $request->validated()
        );

        return redirect()
            ->route('projects.show', $task->project_id)
            ->with('success', 'Task berhasil diperbarui.');
    }

    /**
     * Memperbarui status task.
     */
    public function updateStatus(
        Request $request,
        Task $task
    ): RedirectResponse {

        $request->validate([
            'status' => 'required|in:Todo,In Progress,Review,Done',
        ]);

        $this->taskService->updateTaskStatus(
            $task,
            $request->status
        );

        return redirect()
            ->back()
            ->with('success', 'Status task berhasil diperbarui.');
    }

    /**
     * Menghapus task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $projectId = $task->project_id;

        $this->taskService->deleteTask($task);

        return redirect()
            ->route('projects.show', $projectId)
            ->with('success', 'Task berhasil dihapus.');
    }
}

