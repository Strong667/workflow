<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->query('action')))
            ->when($request->filled('entity'), fn ($q) => $q->where('entity', $request->query('entity')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->where('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->where('created_at', '<=', $request->date('date_to')->endOfDay()))
            ->latest('created_at')
            ->paginate(min($request->integer('per_page', 20), 100))
            ->withQueryString();

        return ActivityLogResource::collection($logs);
    }
}
