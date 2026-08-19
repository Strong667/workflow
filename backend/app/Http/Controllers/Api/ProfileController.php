<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\ActivityLogger;
use App\Services\AvatarStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(private readonly AvatarStorage $avatars)
    {
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (! empty($data['password'])) {
            if (! Hash::check($data['current_password'] ?? '', $user->password)) {
                return response()->json([
                    'message' => 'Текущий пароль указан неверно',
                    'errors'  => ['current_password' => ['Текущий пароль указан неверно']],
                ], 422);
            }
        } else {
            unset($data['password']);
        }

        unset($data['current_password'], $data['password_confirmation']);

        $previousAvatar = $user->avatar;
        $user->update($data);
        $this->avatars->replace($previousAvatar, $user->avatar);
        ActivityLogger::log('update', 'User', $user->id, 'Обновлён профиль пользователя');

        return response()->json(['data' => new UserResource($user->fresh())]);
    }
}
