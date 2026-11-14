<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public const STATUSES = ['進行中', '中止', '完了'];

    public const DEPARTMENTS = ['選択なし', '営業', 'フロント', '工場', '品管', '経理', '総務', '運送'];

    protected $fillable = [
        'name',
        'status',
        'subject_id',
        'team_members',
        'department',
    ];

    protected function casts(): array
    {
        return [
            'team_members' => 'array',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProjectHistory::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class);
    }

    public function balls(): HasMany
    {
        return $this->hasMany(ProjectBall::class);
    }

    public function openBalls(): HasMany
    {
        return $this->hasMany(ProjectBall::class)->where('status', 'open');
    }

    public function latestCommentLine(): string
    {
        $history = $this->relationLoaded('latestHistory')
            ? $this->getRelation('latestHistory')
            : null;

        if ($history === null) {
            return '';
        }

        $text = preg_replace('/\s+/u', ' ', trim((string) $history->content)) ?? '';

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) > 80) {
            $text = mb_substr($text, 0, 80).'…';
        }

        $author = trim((string) $history->author);

        return '「'.$text.'」'.($author !== '' ? ' '.$author : '');
    }
}
