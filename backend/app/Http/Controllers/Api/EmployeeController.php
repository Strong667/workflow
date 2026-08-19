<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\ActivityLogger;
use App\Services\AvatarStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function __construct(private readonly AvatarStorage $avatars)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $sort      = in_array($request->query('sort'), ['first_name', 'last_name', 'position', 'hire_date', 'created_at'], true)
            ? $request->query('sort')
            : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $employees = Employee::query()
            ->with(['department', 'user'])
            ->withCount('tasks')
            ->search($request->query('search'))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('position'), fn ($q) => $q->where('position', 'like', '%'.$request->query('position').'%'))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 10), 100))
            ->withQueryString();

        return EmployeeResource::collection($employees);
    }

    public function store(EmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());
        ActivityLogger::log('create', $employee, null, "Создан сотрудник {$employee->full_name}");

        return response()->json(['data' => new EmployeeResource($employee->load('department'))], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $employee->load(['department', 'user', 'tasks' => fn ($q) => $q->latest()]);

        return response()->json(['data' => new EmployeeResource($employee)]);
    }

    public function update(EmployeeRequest $request, Employee $employee): JsonResponse
    {
        $previousAvatar = $employee->avatar;
        $employee->update($request->validated());
        $this->avatars->replace($previousAvatar, $employee->avatar);
        ActivityLogger::log('update', $employee, null, "Обновлён сотрудник {$employee->full_name}");

        return response()->json(['data' => new EmployeeResource($employee->load('department'))]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $name = $employee->full_name;
        $id   = $employee->id;
        $this->avatars->delete($employee->avatar);
        $employee->delete();
        ActivityLogger::log('delete', 'Employee', $id, "Удалён сотрудник {$name}");

        return response()->json(['message' => 'Сотрудник удалён']);
    }
}
