<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class BallReminderWindow
{
    public const LEAD_BUSINESS_DAYS = 3;

    public static function isApproaching(string $today, string $due): bool
    {
        return $due >= $today && $today >= self::startsOn($due);
    }

    public static function isOverdue(string $today, string $due): bool
    {
        return $due < $today;
    }

    /**
     * 期限当日から、土日を飛ばして数えた 3 日前。
     */
    public static function startsOn(string $due): string
    {
        $cursor = Carbon::parse($due)->startOfDay();
        $remaining = self::LEAD_BUSINESS_DAYS;

        while ($remaining > 0) {
            $cursor->subDay();

            if (! $cursor->isWeekend()) {
                $remaining--;
            }
        }

        return $cursor->toDateString();
    }

    /**
     * 平日 8:30 から昼までが朝の枠、13:00 以降が昼の枠。土日と 8:30 前は送らない。
     */
    public static function slot(CarbonInterface $now): ?string
    {
        $now = Carbon::parse($now)->timezone('Asia/Tokyo');

        if ($now->isWeekend()) {
            return null;
        }

        $minutes = ((int) $now->format('G')) * 60 + (int) $now->format('i');

        if ($minutes < (8 * 60 + 30)) {
            return null;
        }

        return $minutes < (13 * 60) ? '0830' : '1300';
    }
}
