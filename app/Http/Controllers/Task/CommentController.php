<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\CommentRequest;
use App\Services\Task\CommentService;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function __construct(
        protected CommentService $commentService
    ) {}

    /**
     * Menerima request kiriman catatan progress dan meneruskannya ke Service.
     */
    public function store(CommentRequest $request): RedirectResponse
    {
        $this->commentService->storeComment($request->validated());

        return redirect()->back()->with('success', 'Catatan log progres baru berhasil disematkan.');
    }
}
