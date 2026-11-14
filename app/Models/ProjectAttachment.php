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

    public function isPreviewImage(): bool
    {
        $type = strtolower((string) $this->file_type);
        $extension = strtolower(pathinfo((string) $this->original_file_name, PATHINFO_EXTENSION));

        return in_array($type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)
            || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public function opensInBrowser(): bool
    {
        $type = strtolower((string) $this->file_type);
        $extension = strtolower(pathinfo((string) $this->original_file_name, PATHINFO_EXTENSION));

        return $this->isPreviewImage()
            || $type === 'application/pdf'
            || $extension === 'pdf';
    }
}
