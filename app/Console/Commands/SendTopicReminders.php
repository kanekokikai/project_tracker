<?php

namespace App\Console\Commands;

use App\Services\TopicReminderService;
use Illuminate\Console\Command;

class SendTopicReminders extends Command
{
    protected $signature = 'topics:remind';

    protected $description = '議題の会議が近いときと過ぎたときに、メンバーへチャットで知らせる';

    public function handle(TopicReminderService $reminders): int
    {
        $sent = $reminders->send();
        $this->info("お知らせを{$sent}通送りました");

        return self::SUCCESS;
    }
}
