<?php

namespace Tests\Unit;

use App\Support\BallReminderWindow;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class BallReminderWindowTest extends TestCase
{
    public function test_a_monday_deadline_starts_on_the_wednesday_before(): void
    {
        $this->assertSame('2026-11-04', BallReminderWindow::startsOn('2026-11-09'));
        $this->assertFalse(BallReminderWindow::isApproaching('2026-11-03', '2026-11-09'));
        $this->assertTrue(BallReminderWindow::isApproaching('2026-11-04', '2026-11-09'));
        $this->assertTrue(BallReminderWindow::isApproaching('2026-11-06', '2026-11-09'));
        $this->assertTrue(BallReminderWindow::isApproaching('2026-11-09', '2026-11-09'));
        $this->assertFalse(BallReminderWindow::isApproaching('2026-11-10', '2026-11-09'));
        $this->assertTrue(BallReminderWindow::isOverdue('2026-11-10', '2026-11-09'));
    }

    public function test_weekends_are_skipped_when_counting_back_to_the_start(): void
    {
        $this->assertSame('2026-11-04', BallReminderWindow::startsOn('2026-11-07'));
        $this->assertSame('2026-11-11', BallReminderWindow::startsOn('2026-11-16'));
    }

    public function test_reminders_go_out_on_weekday_mornings_and_afternoons(): void
    {
        $fridayMorning = Carbon::parse('2026-11-06 08:30', 'Asia/Tokyo');
        $fridayNoon = Carbon::parse('2026-11-06 12:59', 'Asia/Tokyo');
        $fridayAfternoon = Carbon::parse('2026-11-06 13:00', 'Asia/Tokyo');
        $fridayEarly = Carbon::parse('2026-11-06 08:29', 'Asia/Tokyo');
        $saturday = Carbon::parse('2026-11-07 09:00', 'Asia/Tokyo');

        $this->assertSame('0830', BallReminderWindow::slot($fridayMorning));
        $this->assertSame('0830', BallReminderWindow::slot($fridayNoon));
        $this->assertSame('1300', BallReminderWindow::slot($fridayAfternoon));
        $this->assertNull(BallReminderWindow::slot($fridayEarly));
        $this->assertNull(BallReminderWindow::slot($saturday));
    }
}
