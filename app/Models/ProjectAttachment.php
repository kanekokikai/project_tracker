<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAttachment extends Model
{
    public const CREATED_AT = 'uploaded_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'project_id',
        'file_name',
        'original_file_name',
        'file_size',
        'file_type',
        'uploaded_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
