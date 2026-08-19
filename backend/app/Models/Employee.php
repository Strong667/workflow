<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'department_id',
        'position',
        'hire_date',
        'avatar',
    ];

    protected function casts(): array
    {
        return ['hire_date' => 'date'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Аккаунт, под которым сотрудник входит в систему; может отсутствовать. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $term = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($term) {
            $q->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('position', 'like', $term);
        });
    }
}
