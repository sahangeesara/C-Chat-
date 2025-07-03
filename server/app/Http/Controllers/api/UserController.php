<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;

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
    public function searchUser($name)
    {
        $members = User::where('name', 'like', '%' . $name . '%')->get();

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
