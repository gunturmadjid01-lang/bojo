<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Administrator',
                'username' => 'superbojo',
                'email' => 'superadmin@bojo.local',
                'jenis_kelamin' => 'Laki-laki',
                'role' => 'super_admin',
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::updateOrCreate(
                ['username' => $userData['username']],
                [
                    ...$userData,
                    'email_verified_at' => now(),
                    'password' => 'BojoDes@123#',
                ]
            );

            $user->syncRoles([$role]);
        }
    }
}
