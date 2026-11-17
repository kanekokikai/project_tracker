<?php

namespace App\Http\Controllers;

use App\Models\ProjectBall;
use App\Support\BallCalendar;
use App\Support\BallDisplay;
use Illuminate\View\View;

class BallCalendarController extends Controller
{
    public function index(): View
    {
        $today = BallDisplay::today();

        $balls = ProjectBall::query()
            ->where('status', 'open')
            ->with('project:id,name')
            ->orderBy('id')
            ->get();

        $items = [];

        foreach ($balls as $ball) {
            if ($ball->project === null) {
                continue;
            }

            $items[] = [
                'id' => $ball->id,
                'group_id' => $ball->group_id,
                'assignee' => $ball->assignee,
                'task' => $ball->task,
                'due' => $ball->due_date?->toDateString(),
                'start' => $ball->created_at
                    ? $ball->created_at->timezone('Asia/Tokyo')->toDateString()
                    : $today,
                'project_id' => $ball->project_id,
                'project_name' => $ball->project->name,
            ];
        }

        return view('balls.calendar', [
            'calendar' => BallCalendar::build($items, $today),
        ]);
    }
}
