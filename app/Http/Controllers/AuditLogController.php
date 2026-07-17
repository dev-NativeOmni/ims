<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()
            ->with('user')
            ->when($request->filled('event'), function ($query) use ($request) {
                $query->where('action', $request->string('event')->toString());
            })
            ->when($request->filled('auditable_label'), function ($query) use ($request) {
                $query->where('auditable_label', $request->string('auditable_label')->toString());
            })
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('user_id', $request->integer('user_id'));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->date('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->date('date_to'));
            });

        $summaryQuery = clone $query;

        $auditLogs = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'today' => (clone $summaryQuery)->whereDate('created_at', today())->count(),

            'total_logs' => (clone $summaryQuery)->count(),
            'logs_today' => (clone $summaryQuery)->whereDate('created_at', today())->count(),
            'today_logs' => (clone $summaryQuery)->whereDate('created_at', today())->count(),

            'creates_today' => (clone $summaryQuery)
                ->whereDate('created_at', today())
                ->whereIn('action', ['created', 'create', 'store'])
                ->count(),

            'updates_today' => (clone $summaryQuery)
                ->whereDate('created_at', today())
                ->whereIn('action', ['updated', 'update'])
                ->count(),

            'deletes_today' => (clone $summaryQuery)
                ->whereDate('created_at', today())
                ->whereIn('action', ['deleted', 'delete', 'destroy'])
                ->count(),

            'system_today' => (clone $summaryQuery)
                ->whereDate('created_at', today())
                ->whereNull('user_id')
                ->count(),
        ];

        $events = AuditLog::query()
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $auditableLabels = AuditLog::query()
            ->select('auditable_label')
            ->whereNotNull('auditable_label')
            ->distinct()
            ->orderBy('auditable_label')
            ->pluck('auditable_label');

        return view('audit-logs.index', [
            'auditLogs' => $auditLogs,
            'logs' => $auditLogs,
            'summary' => $summary,
            'events' => $events,
            'auditableLabels' => $auditableLabels,
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('audit-logs.show', [
            'auditLog' => $auditLog,
        ]);
    }
}
