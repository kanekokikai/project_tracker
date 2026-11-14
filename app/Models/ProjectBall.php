<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ProjectBall extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'project_id',
        'group_id',
        'assignee',
        'ball_type',
        'task',
        'due_date',
        'status',
        'created_by',
        'done_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'done_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * 同じ投げの未完了ボール。一人に投げたボールは自分だけ。
     *
     * @return Collection<int, ProjectBall>
     */
    public function openSiblings(): Collection
    {
        if ($this->group_id === null) {
            return collect([$this])->filter(fn (ProjectBall $ball) => $ball->isOpen())->values();
        }

        return static::query()
            ->where('group_id', $this->group_id)
            ->where('status', 'open')
            ->orderBy('id')
            ->get();
    }

    /**
     * 同じ投げで閉じているボール。一人に投げたボールは自分だけ。
     *
     * @return Collection<int, ProjectBall>
     */
    public function doneSiblings(): Collection
    {
        if ($this->group_id === null) {
            return collect([$this])->reject(fn (ProjectBall $ball) => $ball->isOpen())->values();
        }

        return static::query()
            ->where('group_id', $this->group_id)
            ->where('status', 'done')
            ->orderBy('id')
            ->get();
    }
}
