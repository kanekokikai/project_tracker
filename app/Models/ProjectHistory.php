<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHistory extends Model
{
    public const UPDATED_AT = null;

    public const KIND_COMMENT = 'comment';

    public const KIND_QUESTION = 'question';

    public const KIND_DECISION = 'decision';

    public const KIND_BALL = 'ball';

    public const KIND_DONE = 'done';

    protected $table = 'project_history';

    protected $fillable = [
        'project_id',
        'content',
        'status',
        'author',
        'kind',
        'addressed_to',
        'ball_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function ball(): BelongsTo
    {
        return $this->belongsTo(ProjectBall::class, 'ball_id');
    }
}
