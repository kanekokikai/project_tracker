<?php

namespace App\Http\Controllers;

use App\Models\ChatworkMember;
use App\Services\ChatworkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatworkMemberController extends Controller
{
    public function index(): View
    {
        return view('chatwork.index', [
            'hideSidebar' => true,
            'isChatworkAdmin' => true,
        ]);
    }

    public function list(): JsonResponse
    {
        $members = ChatworkMember::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'id',
                'member_name',
                'chatwork_account_id',
                'note',
                'sort_order',
                'updated_at',
            ]);

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }

    public function roomMembers(ChatworkService $chatworkService): JsonResponse
    {
        $members = $chatworkService->fetchRoomMembers();

        if ($members === null) {
            return response()->json([
                'success' => false,
                'message' => 'Chatworkルームメンバーの取得に失敗しました',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:chatwork_members,id'],
            'member_name' => ['required', 'string', 'max:100'],
            'chatwork_account_id' => ['required', 'string', 'regex:/^\d+$/', 'max:50'],
            'note' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'member_name.required' => '名前を入力してください',
            'chatwork_account_id.required' => 'ChatworkアカウントIDを入力してください',
            'chatwork_account_id.regex' => 'ChatworkアカウントIDは数字で入力してください',
        ]);

        $memberName = trim($validated['member_name']);
        $accountId = trim($validated['chatwork_account_id']);
        $note = trim($validated['note'] ?? '');
        $sortOrder = (int) ($validated['sort_order'] ?? 0);
        $id = (int) ($validated['id'] ?? 0);

        $duplicateQuery = ChatworkMember::query()->where('member_name', $memberName);
        if ($id > 0) {
            $duplicateQuery->where('id', '!=', $id);
        }

        if ($duplicateQuery->exists()) {
            return response()->json([
                'success' => false,
                'message' => '同じ名前のメンバーが既に登録されています',
            ], 400);
        }

        if ($id > 0) {
            $member = ChatworkMember::query()->findOrFail($id);
            $member->update([
                'member_name' => $memberName,
                'chatwork_account_id' => $accountId,
                'note' => $note !== '' ? $note : null,
                'sort_order' => $sortOrder,
            ]);
        } else {
            if ($sortOrder === 0) {
                $sortOrder = ((int) ChatworkMember::query()->max('sort_order')) + 1;
            }

            $member = ChatworkMember::query()->create([
                'member_name' => $memberName,
                'chatwork_account_id' => $accountId,
                'note' => $note !== '' ? $note : null,
                'sort_order' => $sortOrder,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '保存しました',
            'id' => $member->id,
        ]);
    }

    public function destroy(ChatworkMember $member): JsonResponse
    {
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => '削除しました',
        ]);
    }
}
