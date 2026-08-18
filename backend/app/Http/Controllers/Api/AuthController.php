<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Неверный email или пароль'], 401);
        }

        ActivityLogger::log('login', 'User', auth('api')->id(), 'Вход в систему');

        return $this->tokenResponse($token, auth('api')->user());
    }

    public function logout(): JsonResponse
    {
        ActivityLogger::log('logout', 'User', auth('api')->id(), 'Выход из системы');
        auth('api')->logout();

        return response()->json(['message' => 'Выход выполнен']);
    }

    /**
     * Обновление пары токенов. Маршрут намеренно вынесен из-под jwt.auth:
     * истёкший токен ещё пригоден для refresh в пределах JWT_REFRESH_TTL.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
            $user  = JWTAuth::setToken($token)->authenticate();
        } catch (\Throwable) {
            return response()->json(['message' => 'Не удалось обновить токен'], 401);
        }

        if (! $user) {
            return response()->json(['message' => 'Пользователь не найден'], 401);
        }

        return $this->tokenResponse($token, $user);
    }

    public function me(): JsonResponse
    {
        return response()->json(['data' => new UserResource(auth('api')->user())]);
    }

    private function tokenResponse(string $token, User $user): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60,
            'user'         => new UserResource($user),
        ]);
    }
}
