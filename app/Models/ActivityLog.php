<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const MAX_ENTRIES = 30;

    public const TYPE_PROJECT_CREATED = 'project_created';

    public const TYPE_SUBPROJECT_CREATED = 'subproject_created';

    public const TYPE_PROJECT_DELETED = 'project_deleted';

    public const TYPE_PROJECT_RENAMED = 'project_renamed';

    public const TYPE_MEMBERS_CHANGED = 'members_changed';

    public const TYPE_DEPARTMENT_CHANGED = 'department_changed';

    public const TYPE_STATUS_CHANGED = 'status_changed';

    public const TYPE_ATTACHMENT_ADDED = 'attachment_added';

    public const TYPE_ATTACHMENT_REMOVED = 'attachment_removed';

    public const TYPE_COMMENT_ADDED = 'comment_added';

    public const TYPE_COMMENT_EDITED = 'comment_edited';

    public const TYPE_COMMENT_DELETED = 'comment_deleted';

    protected $fillable = [
        'event_type',
        'author',
        'message',
        'project_id',
        'project_name',
    ];
}
