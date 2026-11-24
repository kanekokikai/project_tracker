<?php

namespace Tests\Unit;

use App\Support\TopicReminderWindow;
use PHPUnit\Framework\TestCase;

class TopicReminderWindowTest extends TestCase
{
    public function test_a_monday_meeting_is_announced_at_14_and_7_days_then_daily_from_3_days(): void
    {
        $due = '2026-12-07';

        $this->assertSame('2026-11-23', TopicReminderWindow::noticeOn($due, 14));
        $this->assertSame('2026-11-30', TopicReminderWindow::noticeOn($due, 7));
        $this->assertSame('notice14', TopicReminderWindow::phase('2026-11-23', $due));
        $this->assertNull(TopicReminderWindow::phase('2026-11-24', $due));
        $this->assertSame('notice7', TopicReminderWindow::phase('2026-11-30', $due));
        $this->assertNull(TopicReminderWindow::phase('2026-12-01', $due));
        $this->assertSame('approaching', TopicReminderWindow::phase('2026-12-04', $due));
        $this->assertSame('approaching', TopicReminderWindow::phase('2026-12-07', $due));
        $this->assertSame('overdue', TopicReminderWindow::phase('2026-12-08', $due));
    }

    public function test_a_weekend_notice_moves_to_the_friday_before(): void
    {
        $this->assertSame('2026-11-20', TopicReminderWindow::noticeOn('2026-12-05', 14));
        $this->assertSame('2026-11-27', TopicReminderWindow::noticeOn('2026-12-05', 7));
        $this->assertSame('notice14', TopicReminderWindow::phase('2026-11-20', '2026-12-05'));
        $this->assertNull(TopicReminderWindow::phase('2026-11-21', '2026-12-05'));

        $this->assertSame('2026-11-20', TopicReminderWindow::noticeOn('2026-12-06', 14));
        $this->assertSame('2026-11-27', TopicReminderWindow::noticeOn('2026-12-06', 7));
        $this->assertSame('notice7', TopicReminderWindow::phase('2026-11-27', '2026-12-06'));
        $this->assertNull(TopicReminderWindow::phase('2026-11-29', '2026-12-06'));
    }
}
