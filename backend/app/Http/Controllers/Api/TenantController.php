<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function show(): JsonResponse
    {
        $tenant = Auth::user()->tenant;
        return response()->json([
            'data' => $tenant
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenant = Auth::user()->tenant;

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'npwp'     => 'nullable|string|max:30',
            'email'    => 'nullable|email|max:255',
            'telepon'  => 'nullable|string|max:20',
            'alamat'   => 'nullable|string',
            'website'  => 'nullable|string|max:255',
            'kota'     => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kode_pos' => 'nullable|string|max:10',
            'default_ppn_rate' => 'nullable|numeric|between:0,99.99',
        ]);

        $tenant->update($validated);

        return response()->json([
            'message' => 'Organization settings updated successfully',
            'data'    => $tenant
        ]);
    }

    /**
     * Setup Wizard – save step data and mark complete on step 3.
     * POST /tenant/setup  { step: 1|2|3, ...fields }
     */
    public function completeSetup(Request $request): JsonResponse
    {
        $tenant = Auth::user()->tenant;
        $step   = (int) $request->input('step', 1);

        if ($step === 1) {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'jenis_usaha' => 'required|in:event_organizer,wedding_organizer,production_house,lainnya',
                'website'     => 'nullable|string|max:255',
            ]);
            $tenant->update($validated);

        } elseif ($step === 2) {
            $validated = $request->validate([
                'npwp'     => 'nullable|string|max:30',
                'email'    => 'nullable|email|max:255',
                'telepon'  => 'nullable|string|max:20',
                'alamat'   => 'nullable|string',
                'kota'     => 'nullable|string|max:100',
                'provinsi' => 'nullable|string|max:100',
                'kode_pos' => 'nullable|string|max:10',
            ]);
            $tenant->update($validated);

        } elseif ($step === 3) {
            // Optional: add team members
            $members      = $request->input('members', []);
            $createdUsers = [];

            foreach ($members as $member) {
                $emailVal = $member['email'] ?? null;
                $nameVal  = $member['name']  ?? null;
                $roleVal  = $member['role']  ?? 'staff';

                if (!$emailVal || !$nameVal) continue;
                if (!in_array($roleVal, ['finance_manager', 'admin', 'staff'])) {
                    $roleVal = 'staff';
                }
                if (User::where('email', $emailVal)->exists()) continue;

                $newUser = User::create([
                    'tenant_id' => $tenant->id,
                    'name'      => $nameVal,
                    'email'     => $emailVal,
                    'password'  => Hash::make('eventbooks123'),
                    'role'      => $roleVal,
                    'is_active' => true,
                ]);

                $createdUsers[] = [
                    'id'    => $newUser->id,
                    'name'  => $newUser->name,
                    'email' => $newUser->email,
                    'role'  => $newUser->role,
                ];
            }

            // Mark setup as complete
            $tenant->update(['is_setup_complete' => true]);

            \App\Models\AuditLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => Auth::id(),
                'activity' => 'Setup Complete',
                'description' => "User " . Auth::user()->name . " menyelesaikan proses Wizard Setup untuk organisasi: \"{$tenant->name}\"",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'message'       => 'Setup completed successfully',
                'created_users' => $createdUsers,
                'tenant'        => $tenant->fresh(),
            ]);
        }

        return response()->json([
            'message' => "Step {$step} saved successfully",
            'tenant'  => $tenant->fresh(),
        ]);
    }

    public function listUsers(): JsonResponse
    {
        $users = User::where('tenant_id', Auth::user()->tenant_id)->get();
        return response()->json([
            'data' => $users
        ]);
    }

    public function inviteUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:owner,finance_manager,admin,staff',
            'password' => 'nullable|string|min:8',
        ]);

        $user = User::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'password'  => Hash::make($validated['password'] ?? 'eventbooks123'),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'User added successfully',
            'data'    => $user
        ], 201);
    }

    public function updateUser(User $user, Request $request): JsonResponse
    {
        if ($user->tenant_id !== Auth::user()->tenant_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role'  => 'required|in:owner,finance_manager,admin,staff',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'data'    => $user
        ]);
    }

    public function destroyUser(User $user): JsonResponse
    {
        if ($user->tenant_id !== Auth::user()->tenant_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Cannot delete yourself'], 400);
        }

        try {
            $user->delete();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cannot delete user because they have activity history. You can suspend them instead.'
            ], 422);
        }

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    public function toggleUserStatus(User $user): JsonResponse
    {
        if ($user->tenant_id !== Auth::user()->tenant_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Cannot suspend yourself'], 400);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => 'User status updated successfully',
            'data'    => $user
        ]);
    }

    public function updateUserPassword(User $user, Request $request): JsonResponse
    {
        if ($user->tenant_id !== Auth::user()->tenant_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        \App\Models\AuditLog::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'activity' => 'Update User Password',
            'description' => "User " . Auth::user()->name . " mengubah password untuk user: \"{$user->name}\"",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    }
}
