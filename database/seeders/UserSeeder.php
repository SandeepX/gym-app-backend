<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'superadmin@gym.com',
                'name' => 'Super Admin',
                'phone' => '01000000000',
                'role' => 'super-admin',
            ],
            [
                'email' => 'admin@gym.com',
                'name' => 'Admin',
                'phone' => '01000000001',
                'role' => 'admin',
            ],
            [
                'email' => 'receptionist@gym.com',
                'name' => 'Receptionist',
                'phone' => '01000000002',
                'role' => 'receptionist',
            ],
            [
                'email' => 'trainer@gym.com',
                'name' => 'Trainer',
                'phone' => '01000000003',
                'role' => 'trainer',
            ],
            [
                'email' => 'member@gym.com',
                'name' => 'Member',
                'phone' => '01000000004',
                'role' => 'member',
            ],
        ];

        collect($users)->each(function (array $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password123'),
                    'phone' => $userData['phone'],
                    'is_active' => true,
                ]
            );

            $role = Role::where('name', $userData['role'])
                ->where('guard_name', 'api')
                ->first();

            $user->roles()->sync([$role->id]);

            $this->command->line("  ✓ {$userData['name']} → {$userData['role']}");
        });
    }
}
