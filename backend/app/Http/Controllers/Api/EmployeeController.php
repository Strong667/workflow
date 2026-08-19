<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AvatarStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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
            ->when($request->filled('role'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('role', $request->query('role'))))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 10), 100))
            ->withQueryString();

        return EmployeeResource::collection($employees);
    }

    /**
     * Карточка и аккаунт заводятся вместе: email из карточки становится
     * логином, поэтому человек может войти сразу после создания.
     */
    public function store(EmployeeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $employee = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => trim("{$data['first_name']} {$data['last_name']}"),
                'email'    => $data['email'],
                'password' => $data['password'],
                'role'     => $data['role'],
                'avatar'   => $data['avatar'] ?? null,
                'language' => 'ru',
                'theme'    => 'light',
            ]);

            return Employee::create(Arr::except($data, ['password', 'role']) + ['user_id' => $user->id]);
        });

        ActivityLogger::log('create', $employee, null, "Создан сотрудник {$employee->full_name} с доступом {$employee->email}");

        return response()->json(['data' => new EmployeeResource($employee->load(['department', 'user']))], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $employee->load(['department', 'user', 'tasks' => fn ($q) => $q->latest()]);

        return response()->json(['data' => new EmployeeResource($employee)]);
    }

    public function update(EmployeeRequest $request, Employee $employee): JsonResponse
    {
        if ($denied = $this->denyTouchingAdmin($request, $employee)) {
            return $denied;
        }

        $data           = $request->validated();
        $previousAvatar = $employee->avatar;

        DB::transaction(function () use ($employee, $data) {
            $employee->update(Arr::except($data, ['password', 'role']));
            $this->syncAccount($employee, $data);
        });

        $this->avatars->replace($previousAvatar, $employee->avatar);
        ActivityLogger::log('update', $employee, null, "Обновлён сотрудник {$employee->full_name}");

        return response()->json(['data' => new EmployeeResource($employee->fresh()->load(['department', 'user']))]);
    }

    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        if ($denied = $this->denyTouchingAdmin($request, $employee)) {
            return $denied;
        }

        if ($employee->user_id !== null && $employee->user_id === $request->user()->id) {
            return response()->json(['message' => 'Нельзя удалить собственную учётную запись'], 422);
        }

        if ($this->isLastAdmin($employee)) {
            return response()->json(['message' => 'Нельзя удалить последнего администратора'], 422);
        }

        $name = $employee->full_name;
        $id   = $employee->id;

        $this->avatars->delete($employee->avatar);

        DB::transaction(function () use ($employee) {
            $employee->user?->delete();
            $employee->delete();
        });

        ActivityLogger::log('delete', 'Employee', $id, "Удалён сотрудник {$name} вместе с доступом");

        return response()->json(['message' => 'Сотрудник удалён']);
    }

    /** Данные аккаунта держим в одном состоянии с карточкой. */
    private function syncAccount(Employee $employee, array $data): void
    {
        $user = $employee->user;

        // Карточка могла остаться без аккаунта с прежней схемы — заводим его
        // при первом сохранении с паролем.
        if ($user === null) {
            if (empty($data['password'])) {
                return;
            }

            $user = User::create([
                'name'     => trim("{$employee->first_name} {$employee->last_name}"),
                'email'    => $employee->email,
                'password' => $data['password'],
                'role'     => $data['role'] ?? User::ROLE_EMPLOYEE,
                'avatar'   => $employee->avatar,
                'language' => 'ru',
                'theme'    => 'light',
            ]);

            $employee->update(['user_id' => $user->id]);

            return;
        }

        $user->update(array_filter([
            'name'     => trim("{$employee->first_name} {$employee->last_name}"),
            'email'    => $employee->email,
            'avatar'   => $employee->avatar,
            'role'     => $data['role'] ?? null,
            'password' => $data['password'] ?? null,
        ], fn ($value, $key) => $value !== null || in_array($key, ['avatar'], true), ARRAY_FILTER_USE_BOTH));
    }

    private function denyTouchingAdmin(Request $request, Employee $employee): ?JsonResponse
    {
        if ($employee->user?->isAdmin() && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Карточками администраторов управляет только администратор'], 403);
        }

        return null;
    }

    private function isLastAdmin(Employee $employee): bool
    {
        return $employee->user?->isAdmin()
            && User::where('role', User::ROLE_ADMIN)->count() <= 1;
    }
}
