<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Regular User',
                'permissions' => [
                    'create_groups',
                    'join_events',
                    'make_reservations',
                    'purchase_badges',
                    'send_messages',
                    'submit_ratings',
                    'trigger_rescue_request',
                ],
            ],
            [
                'name' => 'Admin',
                'permissions' => [
                    'manage_users',
                    'manage_roles',
                    'manage_venues',
                    'manage_offers',
                    'moderate_content',
                    'view_analytics',
                    'manage_teams',
                    'configure_platform',
                    'view_audit_logs',
                ],
            ],
            [
                'name' => 'Seller',
                'permissions' => [
                    'create_venues',
                    'manage_own_venues',
                    'create_offers',
                    'manage_own_offers',
                    'view_own_analytics',
                ],
            ],
            [
                'name' => 'Rescue Team Member',
                'permissions' => [
                    'view_rescue_requests',
                    'accept_rescue_requests',
                    'view_emergency_locations',
                    'resolve_rescue_requests',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            \App\Models\Role::updateOrCreate(
                ['name' => $roleData['name']],
                ['permissions' => $roleData['permissions']]
            );
        }
    }
}
