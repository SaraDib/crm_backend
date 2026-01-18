<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        if ($request->user()->user_type !== 'system') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        return response()->json($permissions);
    }

    /**
     * Store a newly created permission in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'module' => 'required|string|max:50',
            'type' => 'required|in:system,company',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['module'] . ' ' . $validated['name']);

        $permission = Permission::create($validated);

        return response()->json($permission, 201);
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission)
    {
        return response()->json($permission);
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'module' => 'required|string|max:50',
            'type' => 'required|in:system,company',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['module'] . ' ' . $validated['name']);

        $permission->update($validated);

        return response()->json($permission);
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(['message' => 'Permission supprimée avec succès.']);
    }
}
