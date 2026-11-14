<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'created_by',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
