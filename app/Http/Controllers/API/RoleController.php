<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Get all users with their roles (Admin and Super Admin only)
     */
    public function getAllUsers(Request $request)
    {
        try {
            $users = User::with('roles')->paginate(15);

            return response()->json([
                'success' => true,
                'users' => $users,
                'message' => 'Users retrieved successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => 'An error occurred while retrieving users'
            ], 500);
        }
    }

    /**
     * Get all guides
     */
    public function getAllGuides(Request $request)
    {
        try {
            $guides = User::role('guide')->with('roles')->paginate(15);

            return response()->json([
                'success' => true,
                'guides' => $guides,
                'message' => 'Guides retrieved successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching guides: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch guides',
                'error' => 'An error occurred while retrieving guides'
            ], 500);
        }
    }

    /**
     * Create admin user (Super Admin only)
     */
    public function createAdmin(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole('admin');
            $user->load('roles', 'permissions');

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                ],
                'message' => 'Admin user created successfully'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating admin: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create admin',
                'error' => 'An error occurred while creating admin user'
            ], 500);
        }
    }

    /**
     * Update user role (Admin and Super Admin only)
     */
    public function updateUserRole(Request $request, $userId)
    {
        try {
            $request->validate([
                'role' => 'required|string|in:user,guide,admin',
            ]);

            $user = User::findOrFail($userId);
            $currentUser = $request->user();

            // Prevent admin from changing super_admin roles
            if ($user->hasRole('super_admin') && !$currentUser->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'error' => 'Cannot modify super admin roles'
                ], 403);
            }

            // Prevent admin from creating other admins (only super admin can)
            if ($request->role === 'admin' && !$currentUser->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'error' => 'Only super admin can create admin users'
                ], 403);
            }

            // Remove all current roles and assign new one
            $user->syncRoles([$request->role]);
            $user->load('roles');

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                ],
                'message' => 'User role updated successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating user role: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role',
                'error' => 'An error occurred while updating user role'
            ], 500);
        }
    }

    /**
     * Delete user (Admin and Super Admin only)
     */
    public function deleteUser(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $currentUser = $request->user();

            // Prevent admin from deleting super_admin
            if ($user->hasRole('super_admin') && !$currentUser->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'error' => 'Cannot delete super admin users'
                ], 403);
            }

            // Prevent users from deleting themselves
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete yourself',
                    'error' => 'You cannot delete your own account'
                ], 400);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => 'An error occurred while deleting user'
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'users' => User::role('user')->count(),
                'guides' => User::role('guide')->count(),
                'admins' => User::role('admin')->count(),
                'super_admins' => User::role('super_admin')->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'message' => 'User statistics retrieved successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching user stats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user statistics',
                'error' => 'An error occurred while retrieving statistics'
            ], 500);
        }
    }
}
