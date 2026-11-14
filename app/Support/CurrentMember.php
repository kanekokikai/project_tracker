<?php

namespace App\Support;

class CurrentMember
{
    public static function id(): ?int
    {
        $id = session('auth_member_id');

        return is_numeric($id) ? (int) $id : null;
    }

    public static function name(): string
    {
        $name = trim((string) session('auth_member_name', ''));

        return $name !== '' ? $name : '不明';
    }

    public static function check(): bool
    {
        return session('app_authenticated') === true && self::id() !== null;
    }
}
