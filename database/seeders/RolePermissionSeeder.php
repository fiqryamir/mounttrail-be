<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // User permissions
            'make_booking',
            'update_profile',
            'cancel_own_booking',
            'view_own_bookings',
            'rate_guide',
            'write_reviews',

            // Guide permissions
            'edit_own_tours',
            'delete_own_tours',
            'view_own_bookings',
            'manage_own_bookings',
            // 'accept_booking',
            // 'reject_booking',
            'update_tour_status',

            // Admin permissions
            'manage_users',
            'view_all_users',
            'manage_guides',
            'approve_guides',
            'manage_all_tours',
            'view_all_bookings',
            'moderate_reviews',
            'view_analytics',
            'manage_system_settings',

            // Super Admin permissions
            'manage_admins',
            'delete_any_data',
            'system_configuration',
            'backup_restore',
            'access_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // User role (regular users who book tours)
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->givePermissionTo([
            'make_booking',
            'update_profile',
            'cancel_own_booking',
            'view_own_bookings',
            'rate_guide',
            'write_reviews'
        ]);

        // Guide role
        $guide = Role::firstOrCreate(['name' => 'guide']);
        $guide->givePermissionTo([
            'edit_own_tours',
            'delete_own_tours',
            'view_own_bookings',
            'manage_own_bookings',
            // 'accept_booking',
            // 'reject_booking',
            'update_tour_status',
            // Guides can also use the platform as users
            'make_booking',
            'update_profile',
            'cancel_own_booking',
            'view_own_bookings',
            'rate_guide',
            'write_reviews'
        ]);

        // Admin role
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'manage_users',
            'view_all_users',
            'manage_guides',
            'approve_guides',
            'manage_all_tours',
            'view_all_bookings',
            'moderate_reviews',
            'view_analytics',
            'manage_system_settings',
            // Admin can also perform user and guide actions
            'make_booking',
            'update_profile',
            'cancel_own_booking',
            'view_own_bookings',
            'rate_guide',
            'write_reviews',
            'edit_own_tours',
            'delete_own_tours',
            'view_own_bookings',
            'manage_own_bookings',
            // 'accept_booking',
            // 'reject_booking',
            'update_tour_status'
        ]);

        // Super Admin role (highest level access)
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all()); // All permissions
    }
}
