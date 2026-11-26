<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * The audit trail.
 *
 * One list, filtered the way an admin actually asks questions: who did it,
 * what did they do, and what was it about. The detail page shows the values
 * around the change, which is the part a plain "updated" line can never
 * carry.
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'action'       => trim((string) $request->query('action', '')),
            'actor'        => trim((string) $request->query('actor', '')),
            'subject_type' => trim((string) $request->query('subject_type', '')),
            'subject_id'   => trim((string) $request->query('subject_id', '')),
            'from'         => trim((string) $request->query('from', '')),
            'to'           => trim((string) $request->query('to', '')),
        ];

        $logs = AuditLog::query()
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', 'like', $filters['action'] . '%'))
            ->when($filters['actor'] !== '', fn ($q) => $q->where(function ($inner) use ($filters) {
                $inner->where('actor_name', 'like', '%' . $filters['actor'] . '%')
                    ->orWhere('user_id', (int) $filters['actor']);
            }))
            ->when($filters['subject_type'] !== '', fn ($q) => $q->where('subject_type', $filters['subject_type']))
            ->when($filters['subject_id'] !== '', fn ($q) => $q->where('subject_id', $filters['subject_id']))
            ->when($filters['from'] !== '', fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        // The filter lists come from the data, so a filter can never dead-end
        // on a value that does not exist.
        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $subjectTypes = AuditLog::query()->select('subject_type')->distinct()
            ->whereNotNull('subject_type')->orderBy('subject_type')->pluck('subject_type');

        $total = AuditLog::query()->count();

        return view('backEnd.audit_log.index', compact('logs', 'filters', 'actions', 'subjectTypes', 'total'));
    }

    /**
     * One entry, with the values around the change.
     */
    public function show(int $id)
    {
        $log = AuditLog::query()->findOrFail($id);

        return view('backEnd.audit_log.show', compact('log'));
    }
}
