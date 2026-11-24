<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TopicReminderSend;
use App\Support\BallDisplay;
use App\Support\BallReminderWindow;
use App\Support\TopicReminderWindow;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;

class TopicReminderService
{
    public function __construct(private ChatworkService $chatwork) {}

    public function send(?CarbonInterface $now = null): int
    {
        $now = ($now ?? now())->copy()->timezone('Asia/Tokyo');
        $slot = BallReminderWindow::slot($now);

        if ($slot === null || ! $this->chatwork->isEnabled()) {
            return 0;
        }

        $today = $now->toDateString();
        $sent = 0;

        foreach ($this->openDatedTopics() as $project) {
            $due = $project->due_date?->toDateString();

            if ($due === null) {
                continue;
            }

            $phase = TopicReminderWindow::phase($today, $due);

            if ($phase === null) {
                continue;
            }

            $recordSlot = TopicReminderWindow::isOnce($phase) ? 'once' : $slot;

            if ($this->deliver($today, $recordSlot, $phase, 'project:'.$project->id.':'.$due, function () use ($project, $phase) {
                return $this->chatwork->remindTopicMeeting(
                    $project,
                    $this->recipients($project),
                    TopicReminderWindow::headline($phase),
                );
            })) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Project>
     */
    private function openDatedTopics()
    {
        return Project::query()
            ->where('status', '進行中')
            ->whereNotNull('due_date')
            ->with('subject')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return list<string>
     */
    private function recipients(Project $project): array
    {
        $names = [];

        foreach (is_array($project->team_members) ? $project->team_members : [] as $name) {
            if (! is_string($name)) {
                continue;
            }

            $name = trim($name);

            if ($name === '' || $name === BallDisplay::EXTERNAL) {
                continue;
            }

            $names[$name] = $name;
        }

        return array_values($names);
    }

    /**
     * @param  callable(): bool  $send
     */
    private function deliver(string $date, string $slot, string $kind, string $target, callable $send): bool
    {
        try {
            $record = TopicReminderSend::query()->create([
                'sent_on' => $date,
                'slot' => $slot,
                'kind' => $kind,
                'target' => $target,
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        if ($send()) {
            return true;
        }

        $record->delete();

        return false;
    }
}
