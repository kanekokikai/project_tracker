<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class TopicDue
{
    public static function short(?string $due): string
    {
        if ($due === null || $due === '') {
            return '';
        }

        return Carbon::parse($due)->format('Y/n/j');
    }

    public static function label(?string $due, ?string $today = null): string
    {
        if ($due === null || $due === '') {
            return '';
        }

        $today = $today ?? BallDisplay::today();
        $diff = BallDisplay::dayDiff($today, $due);
        $day = self::short($due);

        if ($diff < 0) {
            return $day.' を'.(-$diff).'日超過';
        }

        if ($diff === 0) {
            return $day.' 当日';
        }

        return $day.' あと'.$diff.'日';
    }

    public static function isOverdue(?string $due, ?string $today = null): bool
    {
        if ($due === null || $due === '') {
            return false;
        }

        $today = $today ?? BallDisplay::today();

        return BallDisplay::dayDiff($today, $due) < 0;
    }
}
