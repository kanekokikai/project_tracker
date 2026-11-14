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

    public const TYPE_BALL_THROWN = 'ball_thrown';

    public const TYPE_BALL_DONE = 'ball_done';

    public const TYPE_BALL_REOPENED = 'ball_reopened';

    public const TYPE_BALL_DUE_CHANGED = 'ball_due_changed';

    public const TYPE_SUBJECT_CREATED = 'subject_created';

    public const TYPE_SUBJECT_RENAMED = 'subject_renamed';

    public const TYPE_SUBJECT_DELETED = 'subject_deleted';

    public const TYPE_SUBJECT_CHANGED = 'subject_changed';

    protected $fillable = [
        'event_type',
        'author',
        'message',
        'project_id',
        'project_name',
    ];
}
