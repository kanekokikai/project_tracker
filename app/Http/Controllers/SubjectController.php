<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Subject;
use App\Services\ActivityLogger;
use App\Support\CurrentMember;
use App\Support\TopicOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(): View
    {
        $viewer = CurrentMember::name();

        $subjects = Subject::query()
            ->with('projects.openBalls')
            ->orderBy('name')
            ->get()
            ->each(function (Subject $subject) use ($viewer) {
                $subject->setRelation('projects', TopicOrder::sort($subject->projects, $viewer));
            });

        return view('subjects.index', [
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => '対象の名前を入力してください',
        ]);

        $name = trim($validated['name']);
        $author = CurrentMember::name();
        $existing = Subject::query()->where('name', $name)->first();

        if ($existing) {
            return redirect()
                ->to(route('subjects.index').'#subject-'.$existing->id)
                ->with('status', '同じ名前の対象があるので、そちらを開きました');
        }

        $subject = Subject::query()->create([
            'name' => $name,
            'created_by' => $author,
        ]);

        $this->activityLogger->record(
            ActivityLog::TYPE_SUBJECT_CREATED,
            $author,
            "対象「{$name}」を作成",
        );

        return redirect()
            ->to(route('subjects.index').'#subject-'.$subject->id)
            ->with('status', '対象を作りました。この対象で議題を作れます');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => '対象の名前を入力してください',
        ]);

        $name = trim($validated['name']);
        $previous = $subject->name;

        if ($name === $previous) {
            return redirect()->to(route('subjects.index').'#subject-'.$subject->id);
        }

        $duplicate = Subject::query()
            ->where('name', $name)
            ->whereKeyNot($subject->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => '同じ名前の対象が既にあります'])->withInput();
        }

        $subject->update(['name' => $name]);

        $this->activityLogger->record(
            ActivityLog::TYPE_SUBJECT_RENAMED,
            CurrentMember::name(),
            "対象の名前を「{$previous}」から「{$name}」に変更",
        );

        return redirect()
            ->to(route('subjects.index').'#subject-'.$subject->id)
            ->with('status', '対象の名前を変えました');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->projects()->exists()) {
            return back()->withErrors(['subject' => '議題が残っている対象は削除できません']);
        }

        $name = $subject->name;
        $subject->delete();

        $this->activityLogger->record(
            ActivityLog::TYPE_SUBJECT_DELETED,
            CurrentMember::name(),
            "対象「{$name}」を削除",
        );

        return redirect()
            ->route('subjects.index')
            ->with('status', '対象を削除しました');
    }
}
