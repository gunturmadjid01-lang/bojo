<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Dusun;
use App\Models\RT;
use App\Models\RW;
use App\Models\User;
use App\Services\WilayahAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DusunHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_dusun_rw_and_rt_are_created_in_one_submission(): void
    {
        $desa = app(WilayahAccess::class)->forSystem(fn () => Desa::create(['nama_desa' => 'Desa Uji', 'aktif' => true]));
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->post('/dusun', [
            'desa_id' => $desa->id,
            'nama' => 'Dusun Terpadu',
            'aktif' => true,
            'rws' => [
                ['nomor' => '001', 'aktif' => true, 'rts' => [['nomor' => '001', 'aktif' => true], ['nomor' => '002', 'aktif' => true]]],
                ['nomor' => '002', 'aktif' => true, 'rts' => [['nomor' => '001', 'aktif' => true]]],
            ],
        ]);

        $response->assertRedirect('/dusun');
        $dusun = Dusun::where('nama', 'Dusun Terpadu')->firstOrFail();
        $this->assertSame(2, RW::where('dusun_id', $dusun->id)->count());
        $this->assertSame(3, RT::whereIn('rw_id', RW::where('dusun_id', $dusun->id)->pluck('id'))->count());
    }

    public function test_standalone_rw_and_rt_web_routes_are_removed(): void
    {
        $this->get('/rw')->assertNotFound();
        $this->get('/rt')->assertNotFound();
    }

    public function test_android_api_creates_dusun_rw_and_rt_together(): void
    {
        $desa = app(WilayahAccess::class)->forSystem(fn () => Desa::create(['nama_desa' => 'Desa API', 'aktif' => true]));
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        Sanctum::actingAs($user, ['api:write']);

        $this->postJson('/api/v1/dusun', [
            'desa_id' => $desa->id,
            'nama' => 'Dusun Android',
            'aktif' => true,
            'rws' => [['nomor' => '01', 'aktif' => true, 'rts' => [['nomor' => '01', 'aktif' => true]]]],
        ])->assertCreated()->assertJsonPath('data.rws.0.rts.0.nomor', '01');

        $dusun = Dusun::where('nama', 'Dusun Android')->firstOrFail();
        $this->assertDatabaseHas('r_w_s', ['dusun_id' => $dusun->id, 'nomor' => '01']);
        $this->assertDatabaseHas('r_t_s', ['nomor' => '01']);
    }
}
