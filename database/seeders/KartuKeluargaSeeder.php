<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Dusun;
use App\Models\KartuKeluarga;
use App\Models\RT;
use App\Models\RW;
use Illuminate\Database\Seeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class KartuKeluargaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(\App\Services\WilayahAccess::class)->forSystem(fn () => $this->seedData());
    }

    private function seedData(): void
    {
        $this->ensureWilayahTables();

        $desa = Desa::query()->first();
        if (! $desa) {
            $desa = Desa::query()->create([
                'kode_desa' => '7308012001',
                'nama_desa' => 'Desa Bojo',
                'kecamatan' => 'Mallusetasi',
                'kabupaten' => 'Barru',
                'provinsi' => 'Sulawesi Selatan',
                'kode_pos' => '90753',
                'alamat_kantor' => 'Jl. Poros Bojo',
                'telepon' => '(0427) 123456',
                'email' => 'desabojo@example.com',
                'aktif' => true,
            ]);
        }

        $dusuns = Dusun::query()->where('desa_id', $desa->id)->orderBy('id')->get();
        if ($dusuns->isEmpty()) {
            $dusunNames = ['Dusun Bojo', 'Dusun Cendana', 'Dusun Maju'];
            foreach ($dusunNames as $index => $name) {
                $dusuns->push(Dusun::query()->create([
                    'desa_id' => $desa->id,
                    'kode' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'nama' => $name,
                    'aktif' => true,
                ]));
            }
        }

        foreach ($dusuns as $dusun) {
            $rws = RW::query()->where('dusun_id', $dusun->id)->get();
            if ($rws->isEmpty()) {
                $rws = collect([
                    RW::query()->create(['dusun_id' => $dusun->id, 'nomor' => '01', 'aktif' => true]),
                    RW::query()->create(['dusun_id' => $dusun->id, 'nomor' => '02', 'aktif' => true]),
                ]);
            }

            foreach ($rws as $rw) {
                if (RT::query()->where('rw_id', $rw->id)->count() === 0) {
                    RT::query()->insert([
                        ['rw_id' => $rw->id, 'nomor' => '01', 'aktif' => true],
                        ['rw_id' => $rw->id, 'nomor' => '02', 'aktif' => true],
                    ]);
                }
            }
        }

        $target = 100;
        $created = KartuKeluarga::query()->count();

        while ($created < $target) {
            foreach ($dusuns as $dusun) {
                if ($created >= $target) {
                    break;
                }

                $rws = RW::query()->where('dusun_id', $dusun->id)->where('aktif', true)->get();
                if ($rws->isEmpty()) {
                    $rw = RW::query()->create([
                        'dusun_id' => $dusun->id,
                        'nomor' => '01',
                        'aktif' => true,
                    ]);
                    $rws = collect([$rw]);
                }

                $rtIds = RT::query()->whereIn('rw_id', $rws->pluck('id'))->where('aktif', true)->pluck('id');
                if ($rtIds->isEmpty()) {
                    $rt = RT::query()->create([
                        'rw_id' => $rws->first()->id,
                        'nomor' => '01',
                        'aktif' => true,
                    ]);
                    $rtIds = collect([$rt->id]);
                }

                $rw = $rws->random();
                $rtId = $rtIds->random();

                KartuKeluarga::query()->create([
                    'nomor_kk' => $this->generateNomorKk($created, $dusun->id),
                    'desa_id' => $desa?->id ?? $dusun->desa_id,
                    'dusun_id' => $dusun->id,
                    'rw_id' => $rw->id,
                    'rt_id' => $rtId,
                    'alamat' => fake()->streetAddress(),
                    'kode_pos' => fake()->postcode(),
                    'tanggal_diterbitkan' => fake()->date('Y-m-d', 'now'),
                    'status' => 'aktif',
                ]);

                $created++;
            }
        }
    }

    protected function ensureWilayahTables(): void
    {
        if (! Schema::hasTable('r_w_s')) {
            Schema::create('r_w_s', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dusun_id')->constrained('dusuns')->restrictOnDelete();
                $table->string('nomor', 10);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['dusun_id', 'nomor']);
            });
        }

        if (! Schema::hasTable('r_t_s')) {
            Schema::create('r_t_s', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rw_id')->constrained('r_w_s')->restrictOnDelete();
                $table->string('nomor', 10);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['rw_id', 'nomor']);
            });
        }
    }

    protected function generateNomorKk(int $index, int $dusunId): string
    {
        $prefix = str_pad((string) ($dusunId % 1000), 3, '0', STR_PAD_LEFT);
        $suffix = str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT);

        return '730801' . $prefix . $suffix;
    }
}
