<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = Group::query()
            ->whereHas('memberships', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->with(['creator:id,name,profile_photo_path', 'members:id,name,profile_photo_path'])
            ->latest('id')
            ->get();

        return response()->json(['groups' => $groups]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = [
            'name' => $this->extractGroupName($request),
            'user_ids' => $this->extractMemberIds($request),
        ];

        $validated = Validator::make($payload, [
            'name' => 'required|string|max:120',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|distinct|exists:users,id',
        ])->validate();

        $creator = $request->user();

        $memberIds = collect($validated['user_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === $creator->id)
            ->values();

        if ($memberIds->count() < 1) {
            return response()->json([
                'error' => 'A group must include at least 2 users (including the creator).',
            ], 422);
        }

        $group = DB::transaction(function () use ($validated, $creator, $memberIds) {
            $group = Group::create([
                'name' => $validated['name'],
                'created_by' => $creator->id,
            ]);

            $rows = [[
                'group_id' => $group->id,
                'user_id' => $creator->id,
                'role' => 'admin',
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]];

            foreach ($memberIds as $memberId) {
                $rows[] = [
                    'group_id' => $group->id,
                    'user_id' => $memberId,
                    'role' => 'normal',
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            GroupMember::insert($rows);

            return $group;
        });

        $group->load(['creator:id,name,profile_photo_path', 'members:id,name,profile_photo_path']);

        return response()->json([
            'status' => 'Group created',
            'group' => $group,
        ], 201);
    }

    public function update(Request $request, Group $group): JsonResponse
    {
        $actorMembership = $this->membershipFor($group->id, $request->user()->id);

        if (!$actorMembership) {
            return response()->json(['error' => 'You are not a member of this group.'], 403);
        }

        if (!in_array($actorMembership->role, ['admin', 'co_admin'], true)) {
            return response()->json(['error' => 'Only admin or co-admin can update group details.'], 403);
        }

        $validated = Validator::make([
            'name' => $this->extractGroupName($request),
        ], [
            'name' => 'required|string|max:120',
        ])->validate();

        $group->name = $validated['name'];
        $group->save();
        $group->load(['creator:id,name,profile_photo_path', 'members:id,name,profile_photo_path']);

        return response()->json([
            'status' => 'Group updated',
            'group' => $group,
        ]);
    }

    public function show(Request $request, Group $group): JsonResponse
    {
        $membership = $this->membershipFor($group->id, $request->user()->id);

        if (!$membership) {
            return response()->json(['error' => 'You are not a member of this group.'], 403);
        }

        $group->load(['creator:id,name,profile_photo_path', 'members:id,name,profile_photo_path']);

        return response()->json([
            'group' => $group,
            'your_role' => $membership->role,
        ]);
    }

    public function addMembers(Request $request, Group $group): JsonResponse
    {
        $actorMembership = $this->membershipFor($group->id, $request->user()->id);

        if (!$actorMembership) {
            return response()->json(['error' => 'You are not a member of this group.'], 403);
        }

        if (!in_array($actorMembership->role, ['admin', 'co_admin'], true)) {
            return response()->json(['error' => 'Only admin or co-admin can add members.'], 403);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|distinct|exists:users,id',
        ]);

        $incomingIds = collect($validated['user_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === $group->created_by)
            ->values();

        $existingIds = GroupMember::query()
            ->where('group_id', $group->id)
            ->whereIn('user_id', $incomingIds)
            ->pluck('user_id')
            ->all();

        $newIds = $incomingIds->reject(fn ($id) => in_array($id, $existingIds, true))->values();

        if ($newIds->isEmpty()) {
            return response()->json([
                'status' => 'No new members added',
                'added_user_ids' => [],
            ]);
        }

        $rows = [];
        foreach ($newIds as $newId) {
            $rows[] = [
                'group_id' => $group->id,
                'user_id' => $newId,
                'role' => 'normal',
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        GroupMember::insert($rows);

        return response()->json([
            'status' => 'Members added',
            'added_user_ids' => $newIds->values(),
        ]);
    }

    public function updateMemberRole(Request $request, Group $group, User $user): JsonResponse
    {
        $actorMembership = $this->membershipFor($group->id, $request->user()->id);

        if (!$actorMembership || $actorMembership->role !== 'admin') {
            return response()->json(['error' => 'Only admin can change member roles.'], 403);
        }

        $targetMembership = $this->membershipFor($group->id, $user->id);

        if (!$targetMembership) {
            return response()->json(['error' => 'User is not in this group.'], 404);
        }

        if ($user->id === (int) $group->created_by) {
            return response()->json(['error' => 'Group creator role cannot be changed.'], 422);
        }

        $validated = $request->validate([
            'role' => 'required|string|in:admin,co_admin,normal',
        ]);

        $targetMembership->role = $validated['role'];
        $targetMembership->save();

        return response()->json([
            'status' => 'Role updated',
            'user_id' => $user->id,
            'role' => $targetMembership->role,
        ]);
    }

    public function removeMember(Request $request, Group $group, User $user): JsonResponse
    {
        $actorMembership = $this->membershipFor($group->id, $request->user()->id);

        if (!$actorMembership) {
            return response()->json(['error' => 'You are not a member of this group.'], 403);
        }

        if (!in_array($actorMembership->role, ['admin', 'co_admin'], true)) {
            return response()->json(['error' => 'Only admin or co-admin can remove members.'], 403);
        }

        if ($user->id === (int) $group->created_by) {
            return response()->json(['error' => 'Group creator cannot be removed.'], 422);
        }

        $targetMembership = $this->membershipFor($group->id, $user->id);

        if (!$targetMembership) {
            return response()->json(['error' => 'User is not in this group.'], 404);
        }

        if ($actorMembership->role === 'co_admin' && $targetMembership->role !== 'normal') {
            return response()->json([
                'error' => 'Co-admin can remove only normal users.',
            ], 403);
        }

        $targetMembership->delete();

        return response()->json([
            'status' => 'Member removed',
            'user_id' => $user->id,
        ]);
    }

    public function leave(Request $request, Group $group): JsonResponse
    {
        $membership = $this->membershipFor($group->id, $request->user()->id);

        if (!$membership) {
            return response()->json(['error' => 'You are not a member of this group.'], 403);
        }

        if ($request->user()->id === (int) $group->created_by) {
            return response()->json(['error' => 'Group creator cannot leave the group.'], 422);
        }

        $membership->delete();

        return response()->json(['status' => 'Left group']);
    }

    public function destroy(Request $request, Group $group): JsonResponse
    {
        if ($request->user()->id !== (int) $group->created_by) {
            return response()->json(['error' => 'Only group creator can delete the group.'], 403);
        }

        $group->delete();

        return response()->json(['status' => 'Group deleted']);
    }

    private function membershipFor(int $groupId, int $userId): ?GroupMember
    {
        return GroupMember::query()
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();
    }

    private function extractGroupName(Request $request): ?string
    {
        return $request->input('name')
            ?? $request->input('group_name')
            ?? $request->input('groupName')
            ?? $request->input('title');
    }

    private function extractMemberIds(Request $request): array
    {
        $raw = $request->input('user_ids')
            ?? $request->input('userIds')
            ?? $request->input('member_ids')
            ?? $request->input('members')
            ?? [];

        if (!is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->map(function ($value) {
                if (is_array($value)) {
                    return $value['id'] ?? null;
                }

                if (is_object($value)) {
                    return $value->id ?? null;
                }

                return $value;
            })
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}

