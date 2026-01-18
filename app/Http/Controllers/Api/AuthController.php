<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('roles', 'companies'),
        ]);
    }

    /**
     * Handle Registration (Onboarding)
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Create User
            $user = User::create([
                'uuid' => Str::uuid(),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_type' => 'company',
                'status' => 'active',
            ]);

            // 2. Create Company
            $company = Company::create([
                'uuid' => Str::uuid(),
                'name' => $request->company_name,
                'slug' => Str::slug($request->company_name) . '-' . rand(1000, 9999),
                'email' => $request->email,
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
            ]);

            // 3. Assign Role (Company Admin)
            $adminRole = Role::where('slug', 'company-admin')->first();
            $user->companies()->attach($company->id, [
                'role_id' => $adminRole->id,
                'is_owner' => true,
                'is_active' => true,
            ]);

            // 4. Assign Free Trial Subscription
            $freePlan = SubscriptionPlan::where('slug', 'free-trial')->first();
            Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $freePlan->id,
                'starts_at' => now(),
                'ends_at' => now()->addDays(14),
                'trial_ends_at' => now()->addDays(14),
                'status' => 'active',
                'auto_renew' => false,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load('roles', 'companies'),
            ], 201);
        });
    }

    /**
     * Get Current User Data
     */
    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles', 'companies'));
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }
}
