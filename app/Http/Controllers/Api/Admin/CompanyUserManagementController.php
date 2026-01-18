<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyUserManagementController extends Controller
{
    /**
     * List all users for a specific company (Admin view)
     */
    public function index($companyId)
    {
        $company = Company::findOrFail($companyId);
        
        $users = User::whereHas('companies', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->with(['companies' => function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        }])
        ->get()
        ->map(function ($user) use ($companyId) {
            $company = $user->companies->where('id', $companyId)->first();
            $pivot = $company->pivot;
            
            return [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'status' => $pivot->is_active ? 'active' : 'inactive',
                'role_id' => $pivot->role_id,
                'role_name' => Role::find($pivot->role_id)?->name,
                'department' => $pivot->department,
                'job_title' => $pivot->job_title,
                'is_owner' => (bool)$pivot->is_owner,
                'joined_at' => $pivot->joined_at,
            ];
        });

        return response()->json($users);
    }

    /**
     * Create a new user for a specific company (Admin)
     */
    public function store(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'department' => 'nullable|string',
            'job_title' => 'nullable|string',
        ]);

        // Verify role is a company role
        $role = Role::findOrFail($validated['role_id']);
        if (!in_array($role->slug, ['company-admin', 'manager', 'agent'])) {
            return response()->json(['message' => 'Invalid role for company user'], 400);
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'user_type' => 'company',
            'status' => 'active',
        ]);

        $user->companies()->attach($companyId, [
            'role_id' => $validated['role_id'],
            'department' => $validated['department'],
            'job_title' => $validated['job_title'],
            'is_owner' => false,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user
        ], 201);
    }

    /**
     * Update a company user (Admin)
     */
    public function update(Request $request, $companyId, $userId)
    {
        $company = Company::findOrFail($companyId);
        $user = User::findOrFail($userId);
        
        $exists = DB::table('company_users')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->exists();
            
        if (!$exists) {
            return response()->json(['message' => 'Utilisateur non trouvé dans cette entreprise'], 404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($userId)],
            'password' => 'nullable|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'department' => 'nullable|string',
            'job_title' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->companies()->updateExistingPivot($companyId, [
            'role_id' => $validated['role_id'],
            'department' => $validated['department'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'is_active' => ($validated['status'] ?? 'active') === 'active',
        ]);

        return response()->json(['message' => 'Utilisateur mis à jour avec succès']);
    }

    /**
     * Remove a user from a company (Admin)
     */
    public function destroy($companyId, $userId)
    {
        $company = Company::findOrFail($companyId);
        $user = User::findOrFail($userId);
        
        $pivot = DB::table('company_users')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->first();

        if (!$pivot) {
            return response()->json(['message' => 'Utilisateur non trouvé dans cette entreprise'], 404);
        }

        if ($pivot->is_owner) {
            return response()->json(['message' => 'Impossible de supprimer le propriétaire'], 403);
        }

        $user->companies()->detach($companyId);
        
        return response()->json(['message' => 'Utilisateur retiré de l\'entreprise']);
    }
}
