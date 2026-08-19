<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeAccountRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeAccountController extends Controller
{
    /**
     * Выдаёт сотруднику доступ в систему: заводит аккаунт на его email
     * и связывает с карточкой. Имя и почта берутся из справочника,
     * чтобы данные не разъезжались.
     */
    public function store(EmployeeAccountRequest $request, Employee $employee): JsonResponse
    {
        if ($employee->user_id !== null) {
            return response()->json(['message' => 'У сотрудника уже есть доступ'], 422);
        }

        if (User::where('email', $employee->email)->exists()) {
            return response()->json([
                'message' => 'Аккаунт с таким email уже существует — привяжите его в разделе «Пользователи»',
                'errors'  => ['email' => ['Аккаунт с таким email уже существует']],
            ], 422);
        }

        $user = User::create([
            'name'     => $employee->full_name,
            'email'    => $employee->email,
            'password' => $request->validated('password'),
            'role'     => $request->validated('role', User::ROLE_EMPLOYEE),
            'avatar'   => $employee->avatar,
            'language' => 'ru',
            'theme'    => 'light',
        ]);

        $employee->update(['user_id' => $user->id]);
        ActivityLogger::log('create', 'User', $user->id, "Выдан доступ сотруднику {$employee->full_name}");

        return response()->json(['data' => new EmployeeResource($employee->load(['user', 'department']))], 201);
    }

    /**
     * Привязывает к карточке уже существующий аккаунт — например, когда
     * человек зарегистрирован раньше, чем появился в справочнике.
     */
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($employee->id)],
        ]);

        if ($employee->user_id !== null) {
            return response()->json(['message' => 'У сотрудника уже есть доступ'], 422);
        }

        $user = User::findOrFail($validated['user_id']);

        if ($user->isAdmin() && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Аккаунтами администраторов управляет только администратор'], 403);
        }

        $employee->update(['user_id' => $user->id]);
        ActivityLogger::log('update', $employee, null, "К сотруднику {$employee->full_name} привязан аккаунт {$user->email}");

        return response()->json(['data' => new EmployeeResource($employee->load(['user', 'department']))]);
    }

    /**
     * Отвязывает аккаунт от карточки. Сам аккаунт остаётся — им управляют
     * в разделе «Пользователи», чтобы удаление не происходило неявно.
     */
    public function destroy(Employee $employee): JsonResponse
    {
        if ($employee->user_id === null) {
            return response()->json(['message' => 'У сотрудника нет доступа'], 422);
        }

        $userId = $employee->user_id;
        $employee->update(['user_id' => null]);
        ActivityLogger::log('update', $employee, null, "Отвязан аккаунт #{$userId} от сотрудника {$employee->full_name}");

        return response()->json(['data' => new EmployeeResource($employee->load('department'))]);
    }
}
