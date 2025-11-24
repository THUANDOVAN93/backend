<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Models\User;
use App\Enums\UserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $role = $request->input('role');
        $status = $request->input('status');

        $users = User::with('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'] ?? UserStatus::ACTIVE,
        ]);

        $user->ulid = Str::ulid()->toBase32();
        $user->save();

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('roles'),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(string $ulid): JsonResponse
    {
        $user = User::with('roles')->where('ulid', $ulid)->firstOrFail();

        return response()->json(['user' => $user]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, string $ulid): JsonResponse
    {
        $user = User::where('ulid', $ulid)->firstOrFail();
        $validated = $request->validated();

        // Prepare update data
        $updateData = array_filter($validated, fn($key) => $key !== 'roles', ARRAY_FILTER_USE_KEY);

        // Hash password if provided
        if (isset($updateData['password'])) {
            $updateData['password'] = Hash::make($updateData['password']);
        }

        $user->update($updateData);

        // Sync roles if provided
        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Update the user's status.
     */
    public function updateStatus(UpdateUserStatusRequest $request, string $ulid): JsonResponse
    {
        $user = User::where('ulid', $ulid)->firstOrFail();
        $validated = $request->validated();

        $user->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(string $ulid): JsonResponse
    {
        $user = User::where('ulid', $ulid)->firstOrFail();

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete yourself'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get available user statuses.
     */
    public function getStatuses(): JsonResponse
    {
        return response()->json([
            'statuses' => UserStatus::options()
        ]);
    }
}
