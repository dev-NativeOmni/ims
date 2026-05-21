<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $auditLogs = AuditLog::query()
            ->with('user')
            ->when($request->filled('event'), function ($query) use ($request) {
                $query->where('event', $request->string('event')->toString());
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('auditable_type', 'like', "%{$search}%")
                        ->orWhere('auditable_id', $search)
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('audit-logs.index', compact('auditLogs'));
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('audit-logs.show', compact('auditLog'));
    }
}