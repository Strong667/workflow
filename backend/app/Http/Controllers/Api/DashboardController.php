<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\TaskResource;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // Рядовой сотрудник видит сводку по своим задачам, а не по компании.
        $ownEmployeeId = $request->user()->hasRole(User::ROLE_ADMIN, User::ROLE_MANAGER)
            ? null
            : ($request->user()->employee?->id ?? 0);

        $tasks = fn () => Task::query()
            ->when($ownEmployeeId !== null, fn ($q) => $q->where('employee_id', $ownEmployeeId));

        $tasksByStatus = $tasks()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $tasksByPriority = $tasks()
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $employeesByDepartment = Department::query()
            ->withCount('employees')
            ->orderByDesc('employees_count')
            ->get()
            ->map(fn (Department $d) => ['name' => $d->name, 'total' => $d->employees_count]);

        $tasksPerWeek = collect(range(5, 0))->map(function (int $weeksAgo) use ($tasks) {
            $start = Carbon::now()->subWeeks($weeksAgo)->startOfWeek();
            $end   = (clone $start)->endOfWeek();

            return [
                'label'   => $start->format('d.m'),
                'created' => $tasks()->whereBetween('created_at', [$start, $end])->count(),
                'done'    => $tasks()->where('status', 'done')->whereBetween('updated_at', [$start, $end])->count(),
            ];
        });

        return response()->json([
            'data' => [
                'totals' => [
                    'employees'   => Employee::count(),
                    'departments' => Department::count(),
                    'tasks'       => $tasks()->count(),
                    'overdue'     => $tasks()->whereNotNull('deadline')
                        ->where('status', '!=', 'done')
                        ->whereDate('deadline', '<', now())
                        ->count(),
                ],
                'tasks_by_status'         => $tasksByStatus,
                'tasks_by_priority'       => $tasksByPriority,
                'employees_by_department' => $employeesByDepartment,
                'tasks_per_week'          => $tasksPerWeek,
                'recent_tasks'            => TaskResource::collection(
                    $tasks()->with('employee')->latest()->limit(5)->get()
                ),
                'recent_activity' => ActivityLogResource::collection(
                    $ownEmployeeId === null
                        ? ActivityLog::with('user')->latest('created_at')->limit(8)->get()
                        : collect()
                ),
            ],
        ]);
    }
}
