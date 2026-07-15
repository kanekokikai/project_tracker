<?php

namespace App\Services;

use App\Models\ChatworkMember;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatworkService
{
    public function isEnabled(): bool
    {
        return config('chatwork.enabled')
            && config('chatwork.api_token')
            && config('chatwork.room_id');
    }

    public function notifyNewComment(Project $project, string $author, string $content): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $project->loadMissing('parent');

        $fullProjectName = $this->buildProjectName($project);
        $teamMembers = $this->collectTeamMembers($project);
        $projectUrl = $this->buildProjectUrl($project);

        $message = "【{$fullProjectName}】に新しいコメントが追加されました\n\n";
        $message .= "投稿者: {$author}\n";
        $message .= "コメント内容:\n{$content}";

        if ($projectUrl !== '') {
            $message .= "\n\n{$projectUrl}";
        }

        $this->send($teamMembers, $message);
    }

    private function buildProjectUrl(Project $project): string
    {
        $base = (string) config('chatwork.project_base_url', '');

        if ($base === '') {
            return '';
        }

        return $base . '/#project-' . $project->id;
    }

    /**
     * @param  list<string>  $teamMembers
     */
    public function send(array $teamMembers, string $message): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $roomId = config('chatwork.room_id');
        $apiToken = config('chatwork.api_token');
        $body = $this->buildMessageBody($teamMembers, $message);

        try {
            $response = Http::withHeaders([
                'X-ChatWorkToken' => $apiToken,
            ])->asForm()->post(
                "https://api.chatwork.com/v2/rooms/{$roomId}/messages",
                ['body' => $body]
            );

            if ($response->successful()) {
                return true;
            }

            Log::warning('Chatwork API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Chatwork notification failed', [
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    private function buildProjectName(Project $project): string
    {
        if ($project->parent) {
            return "{$project->parent->name} > {$project->name}";
        }

        return $project->name;
    }

    /**
     * @return list<string>
     */
    private function collectTeamMembers(Project $project): array
    {
        $members = is_array($project->team_members) ? $project->team_members : [];

        if ($project->parent) {
            $parentMembers = is_array($project->parent->team_members)
                ? $project->parent->team_members
                : [];

            $members = array_values(array_unique(array_merge($members, $parentMembers)));
        }

        return array_values(array_filter($members, fn ($member) => is_string($member) && $member !== ''));
    }

    /**
     * @param  list<string>  $teamMembers
     */
    public function buildMessageBody(array $teamMembers, string $message): string
    {
        if ($teamMembers === []) {
            return $message;
        }

        $mapping = ChatworkMember::mapping();
        $toPrefix = '';

        foreach ($teamMembers as $member) {
            $chatworkAccount = $mapping[$member] ?? $member;
            $toPrefix .= "[To:{$chatworkAccount}]";
        }

        return $toPrefix."\n".$message;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function fetchRoomMembers(): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $roomId = config('chatwork.room_id');
        $apiToken = config('chatwork.api_token');

        try {
            $response = Http::withHeaders([
                'X-ChatWorkToken' => $apiToken,
            ])->get("https://api.chatwork.com/v2/rooms/{$roomId}/members");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Chatwork room members fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Chatwork room members fetch exception', [
                'message' => $exception->getMessage(),
            ]);
        }

        return null;
    }
}
