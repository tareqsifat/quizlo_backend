<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private const SORTABLE = ['created_at', 'method', 'path', 'status_code', 'duration_ms'];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'   => ['nullable', 'in:' . implode(',', self::SORTABLE)],
            'sort_dir'  => ['nullable', 'in:asc,desc'],
            'search'    => ['nullable', 'string', 'max:200'],
            'user_id'   => ['nullable', 'integer'],
        ]);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $page = ActivityLog::with('user:id,name,email,is_admin')
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $like = '%' . addcslashes($v, '%_\\') . '%';
                $q->where(fn ($w) => $w->where('path', 'like', $like)
                    ->orWhere('action', 'like', $like)
                    ->orWhere('method', 'like', $like)
                    ->orWhere('ip', 'like', $like));
            })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', $sortDir) // stable order for rows sharing the same sort value
            ->paginate($filters['per_page'] ?? 10);

        return response()->json([
            'success' => true,
            'data'    => $page->items(),
            'meta'    => [
                'current_page' => $page->currentPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
                'last_page'    => $page->lastPage(),
            ],
        ]);
    }

    /** Admin users, for the activity log "find by admin" filter. */
    public function admins(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => User::where('is_admin', true)->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }
}
