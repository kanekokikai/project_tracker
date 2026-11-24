<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class TopicReminderWindow
{
    public const NOTICE_LEADS = [14, 7];

    public const DAILY_LEAD = 3;

    /**
     * 14日前・7日前はカレンダー日。その日が土日なら、前の金曜に1回だけ知らせる。
     */
    public static function noticeOn(string $due, int $leadDays): string
    {
        $target = Carbon::parse($due)->startOfDay()->subDays($leadDays);

        if ($target->isSunday()) {
            $target->subDays(2);
        } elseif ($target->isSaturday()) {
            $target->subDay();
        }

        return $target->toDateString();
    }

    /**
     * 3日前から当日は approaching、当日を過ぎたら overdue。
     * 14日前と7日前は、その日（土日なら前の金曜）だけ。
     */
    public static function phase(string $today, string $due): ?string
    {
        $days = BallDisplay::dayDiff($today, $due);

        if ($days < 0) {
            return 'overdue';
        }

        if ($days <= self::DAILY_LEAD) {
            return 'approaching';
        }

        if ($today === self::noticeOn($due, 7)) {
            return 'notice7';
        }

        if ($today === self::noticeOn($due, 14)) {
            return 'notice14';
        }

        return null;
    }

    public static function headline(string $phase): string
    {
        return match ($phase) {
            'notice14' => 'この議題の会議が2週間後です',
            'notice7' => 'この議題の会議が1週間後です',
            'approaching' => 'この議題の会議が迫っています。準備はできていますか？',
            'overdue' => '会議の日を過ぎた議題があります。対応してください',
            default => '',
        };
    }

    public static function isOnce(string $phase): bool
    {
        return $phase === 'notice14' || $phase === 'notice7';
    }
}
