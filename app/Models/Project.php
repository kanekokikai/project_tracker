<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public const STATUSES = ['未着手', '進行中', 'レビュー中', '保留中', '完了', '中止'];

    public const DEPARTMENTS = ['選択なし', '営業', 'フロント', '工場', '品管', '経理', '総務', '運送'];

    protected $fillable = [
        'name',
        'status',
        'parent_id',
        'team_members',
        'department',
    ];

    protected function casts(): array
    {
        return [
            'team_members' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProjectHistory::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class);
    }

    public function isParent(): bool
    {
        return $this->parent_id === null;
    }
}
