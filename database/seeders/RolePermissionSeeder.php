<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'members' => [
                'view members',
                'create members',
                'edit members',
                'delete members',
            ],
            'plans' => [
                'view plans',
                'create plans',
                'edit plans',
                'delete plans',
            ],
            'subscriptions' => [
                'view subscriptions',
                'create subscriptions',
                'edit subscriptions',
                'delete subscriptions',
                'freeze subscriptions',
            ],
            'attendance' => [
                'view attendance',
                'create attendance',
                'edit attendance',
                'delete attendance',
            ],
            'payments' => [
                'view payments',
                'create payments',
                'edit payments',
                'delete payments',
            ],
            'trainers' => [
                'view trainers',
                'create trainers',
                'edit trainers',
                'delete trainers',
                'assign trainers',
            ],
            'dashboard' => [
                'view dashboard',
            ],
            'roles' => [
                'view roles',
                'create roles',
                'edit roles',
                'delete roles',
                'assign roles',
            ],
        ];

        foreach ($permissions as $group => $list) {
            foreach ($list as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'api',
                ]);
            }
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'api',
        ]);
        $superAdmin->syncPermissions(Permission::all());

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api',
        ]);
        $admin->syncPermissions(
            Permission::where('name', 'not like', '%roles%')->get()
        );

        $receptionist = Role::firstOrCreate([
            'name' => 'receptionist',
            'guard_name' => 'api',
        ]);
        $receptionist->syncPermissions([
            'view members',
            'view plans',
            'view subscriptions',
            'create attendance',
            'view attendance',
            'view payments',
            'create payments',
            'view dashboard',
        ]);

        $trainer = Role::firstOrCreate([
            'name' => 'trainer',
            'guard_name' => 'api',
        ]);
        $trainer->syncPermissions([
            'view subscriptions',
            'view attendance',
            'view payments',
            'view plans',
            'view members',
            'create attendance',
            'view trainers',
        ]);

        $member = Role::firstOrCreate([
            'name' => 'member',
            'guard_name' => 'api',
        ]);

        $member->syncPermissions([
            'view subscriptions',
            'view attendance',
            'view payments',
            'view plans',
        ]);

        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@gym.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'phone' => '01000000000',
                'is_active' => true,
            ]
        );
        $superAdminUser->syncRoles(['super-admin']);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@gym.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'phone' => '01000000001',
                'is_active' => true,
            ]
        );
        $adminUser->syncRoles(['admin']);

        $receptionistUser = User::firstOrCreate(
            ['email' => 'receptionist@gym.com'],
            [
                'name' => 'Receptionist',
                'password' => Hash::make('password123'),
                'phone' => '01000000002',
                'is_active' => true,
            ]
        );
        $receptionistUser->syncRoles(['receptionist']);

        $trainerUser = User::firstOrCreate(
            ['email' => 'trainer@gym.com'],
            [
                'name' => 'Trainer',
                'password' => Hash::make('password123'),
                'phone' => '01000000003',
                'is_active' => true,
            ]
        );

        $trainerUser->syncRoles(['trainer']);

        $memberUser = User::firstOrCreate(
            ['email' => 'member@gym.com'],
            [
                'name' => 'Member',
                'password' => Hash::make('password123'),
                'phone' => '01000000004',
                'is_active' => true,
            ]
        );
        $memberUser->syncRoles(['member']);

        $this->command->info('✅ Roles & permissions seeded!');
        $this->command->table(
            ['Role', 'Permissions'],
            Role::with('permissions')->get()->map(fn ($r) => [
                $r->name,
                $r->permissions->pluck('name')->implode(', '),
            ])->toArray()
        );
    }
}
