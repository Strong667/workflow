<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException) {
            return response()->json(['message' => 'Токен истёк', 'code' => 'token_expired'], 401);
        } catch (TokenInvalidException) {
            return response()->json(['message' => 'Токен недействителен', 'code' => 'token_invalid'], 401);
        } catch (\Throwable) {
            return response()->json(['message' => 'Токен не найден', 'code' => 'token_absent'], 401);
        }

        if (! $user) {
            return response()->json(['message' => 'Пользователь не найден'], 401);
        }

        return $next($request);
    }
}
