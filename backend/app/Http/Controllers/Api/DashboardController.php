<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\TaskResource;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $tasksByStatus = Task::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $tasksByPriority = Task::query()
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $employeesByDepartment = Department::query()
            ->withCount('employees')
            ->orderByDesc('employees_count')
            ->get()
            ->map(fn (Department $d) => ['name' => $d->name, 'total' => $d->employees_count]);

        $tasksPerWeek = collect(range(5, 0))->map(function (int $weeksAgo) {
            $start = Carbon::now()->subWeeks($weeksAgo)->startOfWeek();
            $end   = (clone $start)->endOfWeek();

            return [
                'label'   => $start->format('d.m'),
                'created' => Task::whereBetween('created_at', [$start, $end])->count(),
                'done'    => Task::where('status', 'done')->whereBetween('updated_at', [$start, $end])->count(),
            ];
        });

        return response()->json([
            'data' => [
                'totals' => [
                    'employees'   => Employee::count(),
                    'departments' => Department::count(),
                    'tasks'       => Task::count(),
                    'overdue'     => Task::whereNotNull('deadline')
                        ->where('status', '!=', 'done')
                        ->whereDate('deadline', '<', now())
                        ->count(),
                ],
                'tasks_by_status'         => $tasksByStatus,
                'tasks_by_priority'       => $tasksByPriority,
                'employees_by_department' => $employeesByDepartment,
                'tasks_per_week'          => $tasksPerWeek,
                'recent_tasks'            => TaskResource::collection(
                    Task::with('employee')->latest()->limit(5)->get()
                ),
                'recent_activity' => ActivityLogResource::collection(
                    ActivityLog::with('user')->latest('created_at')->limit(8)->get()
                ),
            ],
        ]);
    }
}
