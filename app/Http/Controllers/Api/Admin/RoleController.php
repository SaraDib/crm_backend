<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        if ($request->user()->user_type !== 'system') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $roles = Role::with('permissions')->withCount('permissions')
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->orderBy('level', 'desc')
            ->get();

        return response()->json($roles);
    }

    /**
     * Get all available permissions.
     */
    public function permissions()
    {
        $permissions = Permission::all()->groupBy(['type', 'module']);
        return response()->json($permissions);
    }

    /**
     * Store a new role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'level' => 'required|integer|min:0',
            'type' => 'required|in:system,company',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $role = Role::create($validated);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json($role->load('permissions'), 201);
    }

    /**
     * Display role details with permissions.
     */
    public function show(Role $role)
    {
        return response()->json($role->load('permissions'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'level' => 'required|integer|min:0',
            'type' => 'required|in:system,company',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        $role->update($validated);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json($role->load('permissions'));
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // if ($role->is_default) {
        //     return response()->json(['message' => 'Impossible de supprimer un rôle par défaut.'], 422);
        // }

        $role->delete();
        return response()->json(['message' => 'Rôle supprimé avec succès.']);
    }
}
