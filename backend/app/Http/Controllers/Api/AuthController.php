<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register a new tenant (organisation) with owner user.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'org_name'  => 'required|string|max:255',
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8|confirmed',
        ]);

        return DB::transaction(function () use ($request) {
            // Build a unique slug from org name
            $slug = Str::slug($request->org_name);
            $originalSlug = $slug;
            $i = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $i++;
            }

            $tenant = Tenant::create([
                'name'              => $request->org_name,
                'slug'              => $slug,
                'is_setup_complete' => false,
                'subscription_plan' => 'trial',
                'trial_ends_at'     => Carbon::now()->addDays(30),
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => 'owner',
                'is_active' => true,
            ]);

            \App\Models\AuditLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'activity' => 'Register',
                'description' => "User {$user->name} mendaftar dan membuat organisasi: \"{$tenant->name}\"",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $token = $user->createToken('eventbooks_api_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                    'tenant' => [
                        'id'                => $tenant->id,
                        'name'              => $tenant->name,
                        'slug'              => $tenant->slug,
                        'is_setup_complete' => (bool) $tenant->is_setup_complete,
                        'subscription_plan' => $tenant->subscription_plan,
                        'trial_ends_at'     => $tenant->trial_ends_at,
                        'alamat'            => $tenant->alamat,
                        'email'             => $tenant->email,
                        'telepon'           => $tenant->telepon,
                        'npwp'              => $tenant->npwp,
                        'default_ppn_rate'  => (float) $tenant->default_ppn_rate,
                    ],
                ],
            ], 201);
        });
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credentials invalid. Please check email and password.'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is suspended. Please contact administrator.'
            ], 403);
        }

        // Generate Sanctum Token
        $token = $user->createToken('eventbooks_api_token')->plainTextToken;

        \App\Models\AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'activity' => 'Login',
            'description' => "User {$user->name} berhasil masuk ke sistem",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'tenant' => [
                    'id'                => $user->tenant->id,
                    'name'              => $user->tenant->name,
                    'slug'              => $user->tenant->slug,
                    'is_setup_complete' => (bool) $user->tenant->is_setup_complete,
                    'subscription_plan' => $user->tenant->subscription_plan,
                    'trial_ends_at'     => $user->tenant->trial_ends_at,
                    'alamat'            => $user->tenant->alamat,
                    'email'             => $user->tenant->email,
                    'telepon'           => $user->tenant->telepon,
                    'npwp'              => $user->tenant->npwp,
                    'default_ppn_rate'  => (float) $user->tenant->default_ppn_rate,
                ],
            ],
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        \App\Models\AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'activity' => 'Logout',
            'description' => "User {$user->name} keluar dari sistem",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Revoke the active token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'data' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'tenant' => [
                    'id'                => $user->tenant->id,
                    'name'              => $user->tenant->name,
                    'slug'              => $user->tenant->slug,
                    'is_setup_complete' => (bool) $user->tenant->is_setup_complete,
                    'subscription_plan' => $user->tenant->subscription_plan,
                    'trial_ends_at'     => $user->tenant->trial_ends_at,
                    'alamat'            => $user->tenant->alamat,
                    'email'             => $user->tenant->email,
                    'telepon'           => $user->tenant->telepon,
                    'npwp'              => $user->tenant->npwp,
                    'default_ppn_rate'  => (float) $user->tenant->default_ppn_rate,
                ],
            ],
        ], 200);
    }

    /**
     * Change password for the currently authenticated user.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Password saat ini salah.'
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        \App\Models\AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'activity' => 'Change Password',
            'description' => "User {$user->name} memperbarui kata sandi mereka",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Password berhasil diperbarui.'
        ], 200);
    }
}
