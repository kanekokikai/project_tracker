<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatworkMember extends Model
{
    protected $fillable = [
        'member_name',
        'chatwork_account_id',
        'note',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    public static function mapping(): array
    {
        try {
            $rows = static::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['member_name', 'chatwork_account_id']);

            if ($rows->isEmpty()) {
                return config('chatwork.member_mapping', []);
            }

            return $rows
                ->pluck('chatwork_account_id', 'member_name')
                ->all();
        } catch (\Throwable $exception) {
            return config('chatwork.member_mapping', []);
        }
    }
}
