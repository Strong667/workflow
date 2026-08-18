<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $query = Task::query()
            ->with('employee.department')
            ->search($request->query('search'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->query('priority')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas(
                'employee',
                fn ($e) => $e->where('department_id', $request->integer('department_id'))
            ));

        // Kanban-режим: все задачи, сгруппированные по колонкам.
        if ($request->boolean('board')) {
            $tasks = $query->orderBy('position')->orderByDesc('id')->get();

            return response()->json([
                'data' => collect(Task::STATUSES)->mapWithKeys(fn (string $status) => [
                    $status => TaskResource::collection($tasks->where('status', $status)->values()),
                ]),
            ]);
        }

        return TaskResource::collection(
            $query->latest()->paginate(min($request->integer('per_page', 15), 100))->withQueryString()
        );
    }

    public function store(TaskRequest $request): JsonResponse
    {
        $data             = $request->validated();
        $data['status'] ??= 'todo';
        $data['position'] = (int) Task::where('status', $data['status'])->max('position') + 1;

        $task = Task::create($data);
        ActivityLogger::log('create', $task, null, "Создана задача «{$task->title}»");

        return response()->json(['data' => new TaskResource($task->load('employee'))], 201);
    }

    public function show(Task $task): JsonResponse
    {
        return response()->json(['data' => new TaskResource($task->load('employee.department'))]);
    }

    public function update(TaskRequest $request, Task $task): JsonResponse
    {
        $original = $task->status;
        $task->update($request->validated());

        $description = $original !== $task->status
            ? "Задача «{$task->title}»: статус {$original} → {$task->status}"
            : "Обновлена задача «{$task->title}»";
        ActivityLogger::log('update', $task, null, $description);

        return response()->json(['data' => new TaskResource($task->load('employee'))]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $title = $task->title;
        $id    = $task->id;
        $task->delete();
        ActivityLogger::log('delete', 'Task', $id, "Удалена задача «{$title}»");

        return response()->json(['message' => 'Задача удалена']);
    }

    /** Перенос карточки на канбан-доске: новый статус + порядок в колонке. */
    public function move(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'status'   => ['required', 'in:'.implode(',', Task::STATUSES)],
            'position' => ['required', 'integer', 'min:0'],
        ]);

        $original = $task->status;

        DB::transaction(function () use ($task, $validated) {
            Task::where('status', $validated['status'])
                ->where('id', '!=', $task->id)
                ->where('position', '>=', $validated['position'])
                ->increment('position');

            $task->update($validated);
        });

        if ($original !== $task->status) {
            ActivityLogger::log('move', $task, null, "Задача «{$task->title}»: {$original} → {$task->status}");
        }

        return response()->json(['data' => new TaskResource($task->load('employee'))]);
    }
}
