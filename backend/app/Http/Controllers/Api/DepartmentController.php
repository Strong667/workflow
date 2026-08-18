<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Department::query()
            ->withCount('employees')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->query('search').'%'))
            ->orderBy('name');

        if ($request->boolean('all')) {
            return DepartmentResource::collection($query->get());
        }

        return DepartmentResource::collection(
            $query->paginate(min($request->integer('per_page', 15), 100))->withQueryString()
        );
    }

    public function store(DepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());
        ActivityLogger::log('create', $department, null, "Создан отдел «{$department->name}»");

        return response()->json(['data' => new DepartmentResource($department)], 201);
    }

    public function show(Department $department): JsonResponse
    {
        return response()->json(['data' => new DepartmentResource($department->loadCount('employees'))]);
    }

    public function update(DepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());
        ActivityLogger::log('update', $department, null, "Обновлён отдел «{$department->name}»");

        return response()->json(['data' => new DepartmentResource($department->loadCount('employees'))]);
    }

    public function destroy(Department $department): JsonResponse
    {
        $name = $department->name;
        $id   = $department->id;
        $department->delete();
        ActivityLogger::log('delete', 'Department', $id, "Удалён отдел «{$name}»");

        return response()->json(['message' => 'Отдел удалён']);
    }
}
