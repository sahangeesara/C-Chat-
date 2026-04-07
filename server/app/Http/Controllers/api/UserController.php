<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
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
        $member = User::findOrFail($id);

        if (!$member) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($member);
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
