<?php

namespace App\Services\Task;

use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLog\ActivityLogService;
use Exception;

class CommentService
{
    /**
     * @var ActivityLogService
     */
    protected $activityLogService;

    /**
     * Mendaftarkan ActivityLogService ke dalam Constructor melalui Dependency Injection.
     */
    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Menyimpan catatan komentar baru sekaligus mencatatkan
     * jejak rekamnya ke dalam sistem log aktivitas global.
     *
     * @param array $data
     * @return Comment
     * @throws Exception
     */
    public function storeComment(array $data): Comment
    {
        return DB::transaction(function () use ($data) {
            try {
                // Menyimpan data komentar ke tabel task_comments melalui model Comment
                $comment = Comment::create([
                    'task_id' => $data['task_id'],
                    'user_id' => Auth::id(),
                    'comment' => $data['comment'],
                ]);

                // Ambil informasi nama tugas untuk deskripsi log yang informatif
                $taskTitle = $comment->task ? $comment->task->title : 'ID #' . $data['task_id'];

                // Pemicu Log Audit Trail global menggunakan ActivityLogService milik Anda
                $this->activityLogService->log(
                    Auth::id(),
                    'Tracking Progress',
                    'Menambahkan komentar baru pada task "' . $taskTitle . '"'
                );

                return $comment;

            } catch (Exception $e) {
                throw new Exception('Gagal menyimpan komentar: ' . $e->getMessage());
            }
        });
    }
}
