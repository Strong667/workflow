<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvatarUploadRequest;
use App\Services\AvatarStorage;
use Illuminate\Http\JsonResponse;

class AvatarController extends Controller
{
    public function __construct(private readonly AvatarStorage $storage)
    {
    }

    /**
     * Файл сохраняется сразу и отдаёт URL: форма подставляет его
     * в поле avatar и отправляет обычным JSON вместе с остальными полями.
     */
    public function store(AvatarUploadRequest $request): JsonResponse
    {
        return response()->json([
            'url' => $this->storage->store($request->file('file')),
        ], 201);
    }
}
