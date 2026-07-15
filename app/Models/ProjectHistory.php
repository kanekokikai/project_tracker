<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'project_history';

    protected $fillable = [
        'project_id',
        'content',
        'status',
        'author',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
