<?php

namespace Tests\Feature;

use App\Models\{Desa, Dusun, RW, RT, Penduduk, KartuKeluarga, User, PerangkatDesa, PenugasanPerangkat, JabatanDesa, Kelahiran, Kematian};
use App\Services\WilayahAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Page;
use Spatie\Permission\Models\{Permission, Role};
use Tests\TestCase;

class WilayahAccessTest extends TestCase
{
    use RefreshDatabase;

    private array $areas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->areas = app(WilayahAccess::class)->forSystem(function () {
            $result = [];
            $desa = Desa::create(['nama_desa' => 'Desa A']);
            foreach (['Baji', 'Lain', 'Luar Desa'] as $i => $name) {
                if ($i === 2) $desa = Desa::create(['nama_desa' => 'Desa B']);
                $dusun = Dusun::create(['desa_id' => $desa->id, 'nama' => $name]);
                $rw = RW::create(['dusun_id' => $dusun->id, 'nomor' => '01']);
                $rt = RT::create(['rw_id' => $rw->id, 'nomor' => '01']);
                $penduduk = Penduduk::create(['nama_lengkap' => $name, 'nik' => str_pad((string) ($i + 1), 16, '0', STR_PAD_LEFT), 'jenis_kelamin' => 'Laki-laki', 'dusun_id' => $dusun->id, 'rw_id' => $rw->id, 'rt_id' => $rt->id]);
                $kk = KartuKeluarga::create(['nomor_kk' => 'KK'.$i, 'desa_id' => $desa->id, 'dusun_id' => $dusun->id, 'rw_id' => $rw->id, 'rt_id' => $rt->id, 'alamat' => $name]);
                $result[] = compact('desa', 'dusun', 'rw', 'rt', 'penduduk', 'kk');
            }
            return $result;
        });
    }

    private function actor(?string $level = 'dusun', string $role = 'kepala_dusun', array $dates = []): User
    {
        return app(WilayahAccess::class)->forSystem(function () use ($level, $role, $dates) {
            $user = User::factory()->create();
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
            foreach (\App\Support\ModulePermissions::MODULES as [$table]) {
                foreach (['view', 'create', 'edit', 'delete'] as $action) $user->givePermissionTo(Permission::findOrCreate("$action $table", 'web'));
            }
            if ($level) {
                $perangkat = PerangkatDesa::create(['user_id' => $user->id, 'nama_lengkap' => $user->name]);
                $jabatan = JabatanDesa::create(['kode' => 'CUSTOM-'.$user->id, 'nama' => 'Jabatan Bebas', 'tingkat' => $level]);
                PenugasanPerangkat::create(array_merge(['perangkat_desa_id' => $perangkat->id, 'jabatan_desa_id' => $jabatan->id, 'desa_id' => $this->areas[0]['desa']->id, 'dusun_id' => $level === 'dusun' ? $this->areas[0]['dusun']->id : null, 'status' => 'aktif', 'tanggal_mulai' => today()->subDay(), 'tanggal_selesai' => null], $dates));
            }
            return $user;
        });
    }

    public function test_dusun_lists_form_options_and_dashboard_are_scoped(): void
    {
        $this->actingAs($this->actor());
        $this->get('/penduduk')->assertOk()->assertInertia(fn (Page $p) => $p->has('penduduks', 1)->where('penduduks.0.nama_lengkap', 'Baji'));
        $this->get('/anggota-kartu-keluarga/create')->assertOk()->assertInertia(fn (Page $p) => $p->has('penduduk', 1)->has('kartuKeluargas', 1));
        $this->get('/penduduk/create')->assertOk()->assertInertia(fn (Page $p) => $p->has('dusuns', 1)->has('rws', 1)->has('rts', 1));
        $this->get('/dashboard')->assertOk()->assertInertia(fn (Page $p) => $p->has('residents', 1)->where('jumlahPenduduk', 1));
        $this->assertSame(1, Penduduk::count());
    }

    public function test_desa_and_sekdes_see_all_local_dusuns_but_never_another_desa(): void
    {
        foreach (['kepala_desa', 'sekdes'] as $role) {
            $this->actingAs($this->actor('desa', $role));
            $this->get('/penduduk')->assertOk()->assertInertia(fn (Page $p) => $p->has('penduduks', 2));
            $this->get('/kartu-keluarga/create')->assertOk()->assertInertia(fn (Page $p) => $p->has('dusuns', 2)->has('desas', 1)->has('penduduk', 2));
            $this->get('/penduduk/'.$this->areas[2]['penduduk']->id)->assertNotFound();
        }
    }

    public function test_no_active_assignment_and_date_boundaries(): void
    {
        foreach ([null, ['status' => 'selesai'], ['tanggal_mulai' => today()->addDay()], ['tanggal_selesai' => today()->subDay()]] as $dates) {
            $this->actingAs($this->actor($dates === null ? null : 'dusun', 'kepala_dusun', $dates ?? []));
            $this->get('/penduduk')->assertOk()->assertInertia(fn (Page $p) => $p->has('penduduks', 0));
            $this->get('/kematian/create')->assertOk()->assertInertia(fn (Page $p) => $p->has('penduduk', 0));
            $this->get('/penduduk/'.$this->areas[0]['penduduk']->id)->assertNotFound();
        }
        $this->actingAs($this->actor('dusun', 'kepala_dusun', ['tanggal_mulai' => today(), 'tanggal_selesai' => today()]));
        $this->assertSame(1, Penduduk::count());
    }

    public function test_foreign_detail_update_delete_and_nested_select_payload_are_rejected(): void
    {
        $this->actingAs($this->actor());
        foreach ([1, 2] as $i) {
            $id = $this->areas[$i]['penduduk']->id;
            $this->get('/penduduk/'.$id)->assertNotFound();
            $this->put('/penduduk/'.$id, [])->assertNotFound();
            $this->delete('/penduduk/'.$id)->assertNotFound();
            $this->post('/anggota-kartu-keluarga', ['kartu_keluarga_id' => $this->areas[0]['kk']->id, 'anggotas' => [['penduduk_id' => $id, 'hubungan_keluarga' => 'Anak', 'tanggal_masuk' => '2026-01-01']]])->assertSessionHasErrors('anggotas.0.penduduk_id');
            $this->post('/kematian', ['penduduk_id' => $id, 'tanggal_meninggal' => '2026-01-01'])->assertSessionHasErrors('penduduk_id');
        }
        $this->assertDatabaseCount('anggota_kartu_keluargas', 0);
        $this->assertDatabaseCount('kematians', 0);
        $this->post('/anggota-kartu-keluarga', ['kartu_keluarga_id' => $this->areas[0]['kk']->id, 'anggotas' => [['penduduk_id' => $this->areas[0]['penduduk']->id, 'hubungan_keluarga' => 'Kepala Keluarga', 'tanggal_masuk' => '2026-01-01']]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('anggota_kartu_keluargas', 1);
    }

    public function test_permission_and_super_admin_and_guest_boundaries(): void
    {
        $this->get('/penduduk')->assertRedirect('/login');
        $user = $this->actor();
        $user->syncPermissions([]);
        $this->actingAs($user)->get('/penduduk')->assertForbidden();
        $this->get('/dashboard')->assertOk()->assertInertia(fn (Page $p) => $p->has('residents', 0));
        $this->actingAs($this->actor(null, 'super_admin'))->get('/penduduk')->assertOk()->assertInertia(fn (Page $p) => $p->has('penduduks', 3));
    }

    public function test_all_operational_indexes_and_create_forms_render_under_scope(): void
    {
        $this->actingAs($this->actor());
        foreach (array_keys(\App\Support\ModulePermissions::MODULES) as $module) {
            $this->get('/'.$module)->assertOk();
            if (!in_array($module, ['permissions', 'users'])) $this->get('/'.$module.'/create')->assertOk();
        }
    }

    public function test_job_context_is_explicit_and_does_not_leak(): void
    {
        $access = app(WilayahAccess::class);
        $user = $this->actor();
        $this->assertSame(0, Penduduk::count());
        $this->assertSame(1, $access->forUser($user, fn () => Penduduk::count()));
        $this->assertSame(0, Penduduk::count());
    }

    public function test_population_indexes_return_complete_scoped_statistics(): void
    {
        app(WilayahAccess::class)->forSystem(function (): void {
            Kelahiran::create([
                'penduduk_id' => $this->areas[0]['penduduk']->id,
                'kartu_keluarga_id' => $this->areas[0]['kk']->id,
                'tempat_lahir' => 'Bojo',
                'tanggal_lahir' => '2026-01-15',
            ]);
            Kematian::create([
                'penduduk_id' => $this->areas[0]['penduduk']->id,
                'tanggal_meninggal' => '2025-12-31',
            ]);
        });

        $this->actingAs($this->actor());
        $this->get('/kelahiran?statistik_tahun=2026')->assertOk()->assertInertia(fn (Page $page) => $page
            ->where('statistics.year', 2026)
            ->has('statistics.months', 12)
            ->where('statistics.monthly.0', 1)
            ->where('statistics.monthly.1', 0)
            ->has('statistics.regionValues', 2)
            ->where('statistics.regionValues.0.value', 1)
            ->where('statistics.regionValues.1.value', 0));

        $this->get('/kematian?statistik_tahun=2026')->assertOk()->assertInertia(fn (Page $page) => $page
            ->has('statistics.months', 12)
            ->where('statistics.monthly.0', 0));

        $this->get('/penduduk')->assertOk()->assertInertia(fn (Page $page) => $page
            ->has('statistics.cards', 8)
            ->where('statistics.cards.0.value', 1)
            ->has('statistics.months', 12)
            ->has('statistics.regionValues', 2)
            ->has('statistics.demographics.age', 8)
            ->has('statistics.demographics.gender')
            ->has('statistics.demographics.religion'));
    }
}
