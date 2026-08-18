<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    public const STATUSES   = ['todo', 'in_progress', 'review', 'done'];
    public const PRIORITIES = ['low', 'medium', 'high'];

    protected $fillable = [
        'title',
        'description',
        'employee_id',
        'status',
        'priority',
        'deadline',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'position' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $term = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', $term)->orWhere('description', 'like', $term);
        });
    }

    public function isOverdue(): bool
    {
        return $this->deadline !== null
            && $this->status !== 'done'
            && $this->deadline->isPast();
    }
}
