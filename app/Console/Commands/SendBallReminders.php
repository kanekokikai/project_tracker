<?php

namespace App\Console\Commands;

use App\Services\BallReminderService;
use Illuminate\Console\Command;

class SendBallReminders extends Command
{
    protected $signature = 'balls:remind';

    protected $description = '期限が近いボールは保持者へ、過ぎたボールはメンバー全員へチャットで催促する';

    public function handle(BallReminderService $reminders): int
    {
        $sent = $reminders->send();
        $this->info("催促を{$sent}通送りました");

        return self::SUCCESS;
    }
}
