<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BallReminderSend extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'sent_on',
        'slot',
        'kind',
        'target',
    ];

    protected function casts(): array
    {
        return [
            'sent_on' => 'date',
        ];
    }
}
