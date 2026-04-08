<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }
    public function searchUser(Request $request, $name = null)
    {
        $search = trim((string) ($request->input('query')
            ?? $request->input('search')
            ?? $request->input('name')
            ?? $name
            ?? ''));

        $members = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->when(auth()->check(), function ($query) {
                $query->where('id', '!=', auth()->id());
            })
            ->get();

        return response()->json($members);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $groupColumns = ['chat_groups.id', 'chat_groups.name', 'chat_groups.created_by'];

        if (Schema::hasColumn('chat_groups', 'description')) {
            $groupColumns[] = 'chat_groups.description';
        }

        if (Schema::hasColumn('chat_groups', 'profile_image_path')) {
            $groupColumns[] = 'chat_groups.profile_image_path';
        }

        $member = User::query()
            ->with(['groups' => function ($query) use ($groupColumns) {
                $query->select($groupColumns);
            }])
            ->findOrFail($id);

        $authUser = auth()->user();

        $mutualGroups = collect();
        if ($authUser) {
            $authGroupIds = $authUser->groups()->pluck('chat_groups.id')->all();
            if (!empty($authGroupIds)) {
                $mutualGroups = $member->groups()
                    ->whereIn('chat_groups.id', $authGroupIds)
                    ->get($groupColumns);
            }
        }

        $sameCountryMembers = collect();
        if (!empty($member->country)) {
            $sameCountryMembers = User::query()
                ->where('country', $member->country)
                ->where('id', '!=', $member->id)
                ->select(['id', 'name', 'profile_photo_path', 'phone', 'city', 'country', 'last_seen_at'])
                ->orderBy('name')
                ->limit(100)
                ->get();
        }

        $sameCityMembers = collect();
        if (!empty($member->country) && !empty($member->city)) {
            $sameCityMembers = User::query()
                ->where('country', $member->country)
                ->where('city', $member->city)
                ->where('id', '!=', $member->id)
                ->select(['id', 'name', 'profile_photo_path', 'phone', 'city', 'country', 'last_seen_at'])
                ->orderBy('name')
                ->limit(100)
                ->get();
        }

        return response()->json([
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'profile_photo_path' => $member->profile_photo_path,
            'profile_photo_url' => $member->profile_photo_url,
            'phone' => $member->phone,
            'address' => $member->address,
            'city' => $member->city,
            'country' => $member->country,
            'is_online' => $member->is_online,
            'last_seen_at' => $member->last_seen_at?->toIso8601String(),
            'last_seen_human' => $member->last_seen_human,
            'groups' => $member->groups,
            'mutual_groups' => $mutualGroups,
            'same_country_members' => $sameCountryMembers,
            'same_city_members' => $sameCityMembers,
        ]);
    }

    public function status(string $id)
    {
        $member = User::findOrFail($id);

        return response()->json([
            'id' => $member->id,
            'name' => $member->name,
            'profile_photo_url' => $member->profile_photo_url,
            'is_online' => $member->is_online,
            'last_seen_at' => $member->last_seen_at?->toIso8601String(),
            'last_seen_human' => $member->last_seen_human,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function updateProfile(Request $request)
    {
        if (!$request->hasFile('image')) {
            foreach (['profile_image', 'avatar', 'photo'] as $fileKey) {
                if ($request->hasFile($fileKey)) {
                    $request->files->set('image', $request->file($fileKey));
                    break;
                }
            }
        }

        if ($request->hasFile('image') && !$request->file('image')->isValid()) {
            return response()->json([
                'message' => 'The image failed to upload.',
                'errors' => [
                    'image' => [$request->file('image')->getErrorMessage()],
                ],
            ], 422);
        }

        $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'image' => 'nullable|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/bmp,image/svg+xml|max:10240',
        ]);

        $user = $request->user();

        if ($request->hasFile('image')) {
            if (!empty($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request->file('image')->store('profiles', 'public');
        }

        $user->name = $request->input('name');
        $user->phone = $request->filled('phone') ? trim((string) $request->input('phone')) : null;
        $user->address = $request->filled('address') ? trim((string) $request->input('address')) : null;
        $user->city = $request->filled('city') ? trim((string) $request->input('city')) : null;
        $user->country = $request->filled('country') ? trim((string) $request->input('country')) : null;
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => array_merge($user->toArray(), [
                'profile_photo_url' => $user->profile_photo_path
                    ? asset('storage/' . ltrim($user->profile_photo_path, '/'))
                    : null,
            ]),
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
