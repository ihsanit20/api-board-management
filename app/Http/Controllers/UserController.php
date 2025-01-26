<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // ১. Display a listing of the users (GET /api/users)
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200); // JSON ফরম্যাটে সব ইউজার রিটার্ন করবে
    }

    // ২. Store a newly created user in the database (POST /api/users)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255|unique:users',
            'email' => 'nullable|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'address' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('user_photos', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'address' => $request->address,
            'photo' => $photoPath,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        return response()->json(['message' => 'User created successfully.', 'user' => $user], 201);
    }

    public function show(User $user)
    {
        return response()->json($user, 200);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255|unique:users,phone,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|string',
            'address' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $photoPath = $user->photo;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('user_photos', 'public');
        }

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
            'role' => $request->role,
            'address' => $request->address,
            'photo' => $photoPath,
            'is_active' => $request->has('is_active') ? $request->is_active : $user->is_active,
        ]);

        if (!$user->is_active && Auth::id() === $user->id) {
            Auth::logout();
            return response()->json(['message' => 'You have been logged out as your account is now inactive.'], 200);
        }

        return response()->json(['message' => 'User updated successfully.', 'user' => $user], 200);
    }


    // ৫. Remove the specified user from the database (DELETE /api/users/{id})
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully.'], 200);
    }
}
