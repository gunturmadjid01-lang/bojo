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
                'username' => 'superadmin',
                'email' => 'superadmin@bojo.local',
                'jenis_kelamin' => 'Laki-laki',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Kepala Desa',
                'username' => 'kepala.desa',
                'email' => 'kepala.desa@bojo.local',
                'jenis_kelamin' => 'Laki-laki',
                'role' => 'kepala_desa',
            ],
            [
                'name' => 'Sekretaris Desa',
                'username' => 'sekdes',
                'email' => 'sekdes@bojo.local',
                'jenis_kelamin' => 'Laki-laki',
                'role' => 'sekdes',
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
                    'password' => 'password',
                ]
            );

            $user->syncRoles([$role]);
        }
    }
}
