<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AvatarStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly AvatarStorage $avatars)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.str_replace('%', '\%', (string) $request->query('search')).'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->query('role')))
            ->orderBy('name')
            ->paginate(min($request->integer('per_page', 15), 100))
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = User::create($request->validated() + ['language' => 'ru', 'theme' => 'light']);
        ActivityLogger::log('create', $user, null, "Создан аккаунт {$user->email}");

        return response()->json(['data' => new UserResource($user)], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        if ($denied = $this->denyTouchingAdmin($request, $user)) {
            return $denied;
        }

        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($this->wouldDropLastAdmin($user, $data['role'] ?? $user->role)) {
            return response()->json(['message' => 'Нельзя снять роль администратора с последнего администратора'], 422);
        }

        $user->update($data);
        ActivityLogger::log('update', $user, null, "Обновлён аккаунт {$user->email}");

        return response()->json(['data' => new UserResource($user->fresh())]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($denied = $this->denyTouchingAdmin($request, $user)) {
            return $denied;
        }

        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Нельзя удалить собственный аккаунт'], 422);
        }

        if ($this->wouldDropLastAdmin($user, null)) {
            return response()->json(['message' => 'Нельзя удалить последнего администратора'], 422);
        }

        $email = $user->email;
        $id    = $user->id;
        $this->avatars->delete($user->avatar);
        $user->delete();
        ActivityLogger::log('delete', 'User', $id, "Удалён аккаунт {$email}");

        return response()->json(['message' => 'Аккаунт удалён']);
    }

    /**
     * Менеджер не выдаёт роль администратора — значит, и трогать чужие
     * администраторские аккаунты не должен, иначе ограничение обходится
     * удалением неудобного админа.
     */
    private function denyTouchingAdmin(Request $request, User $user): ?JsonResponse
    {
        if ($user->isAdmin() && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Аккаунтами администраторов управляет только администратор'], 403);
        }

        return null;
    }

    /** Система не должна остаться без администратора. */
    private function wouldDropLastAdmin(User $user, ?string $nextRole): bool
    {
        if (! $user->isAdmin() || $nextRole === User::ROLE_ADMIN) {
            return false;
        }

        return User::where('role', User::ROLE_ADMIN)->count() <= 1;
    }
}
