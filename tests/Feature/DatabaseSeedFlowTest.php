<?php

namespace Tests\Feature;

use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeedFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seed_creates_sample_data_without_manual_location_setup(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, KartuKeluarga::count());
        $this->assertGreaterThan(0, Penduduk::count());
    }
}
