<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(\App\Services\WilayahAccess::class)->forSystem(fn () => $this->seedData());
    }

    private function seedData(): void
    {
        $this->call([
            DesaSeeder::class,
            PendidikanSeeder::class,
            PekerjaanSeeder::class,
            DisabilitasSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,

        ]);
    }
}
