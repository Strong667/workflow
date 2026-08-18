<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(string $action, Model|string $entity, ?int $entityId = null, ?string $description = null): void
    {
        if ($entity instanceof Model) {
            $entityId ??= $entity->getKey();
            $entity = class_basename($entity);
        }

        ActivityLog::create([
            'user_id'     => self::currentUserId(),
            'action'      => $action,
            'entity'      => $entity,
            'entity_id'   => $entityId,
            'description' => $description,
        ]);
    }

    /** Запросы к API аутентифицируются jwt-гвардом, а не сессионным web. */
    private static function currentUserId(): ?int
    {
        return Auth::guard('api')->id() ?? Auth::id();
    }
}
