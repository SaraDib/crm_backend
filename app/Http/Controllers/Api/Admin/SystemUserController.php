<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SystemUserController extends Controller
{
    /**
     * Display a listing of system (Super Admin) users.
     */
    public function index(Request $request)
    {
        // Ensure only system admins can access
        if ($request->user()->user_type !== 'system') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::where('user_type', 'system')
            ->with('roles')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    /**
     * Store a new system user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive,suspended',
            'role_id' => 'required|exists:roles,id',
        ]);

        $validated['uuid'] = (string) Str::uuid();
        $validated['user_type'] = 'system';
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->roles()->attach($validated['role_id']);

        return response()->json($user->load('roles'), 201);
    }

    /**
     * Update the specified system user.
     */
    public function update(Request $request, User $user)
    {
        if ($user->user_type !== 'system') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => "required|email|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive,suspended',
            'role_id' => 'required|exists:roles,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->roles()->sync([$validated['role_id']]);

        return response()->json($user->load('roles'));
    }

    /**
     * Remove the specified system user.
     */
    public function destroy(User $user)
    {
        if ($user->user_type !== 'system') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Prevent self-deletion if needed
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
    }
}
