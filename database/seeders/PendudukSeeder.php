<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Dusun;
use App\Models\Pendidikan;
use App\Models\Pekerjaan;
use App\Models\Penduduk;
use App\Models\RT;
use App\Models\RW;
use Illuminate\Database\Seeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PendudukSeeder extends Seeder
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
        $targetCount = 300;
        $created = Penduduk::query()->count();
        $pendidikanIds = Pendidikan::query()->pluck('id')->all();
        $pekerjaanIds = Pekerjaan::query()->pluck('id')->all();
        $locations = [];

        foreach (Dusun::query()->where('aktif', true)->get() as $dusun) {
            foreach (RW::query()->where('dusun_id', $dusun->id)->where('aktif', true)->get() as $rw) {
                foreach (RT::query()->where('rw_id', $rw->id)->where('aktif', true)->get() as $rt) {
                    $locations[] = [$dusun, $rw, $rt];
                }
            }
        }

        if ($locations === []) {
            throw new \RuntimeException('Tidak ada kombinasi Dusun, RW, dan RT aktif untuk seeder Penduduk.');
        }

        while ($created < $targetCount) {
            [$dusun, $rw, $rt] = fake()->randomElement($locations);
            $nik = $this->generateNik($created, $dusun->id);
            while (Penduduk::query()->where('nik', $nik)->exists()) {
                $created++;
                $nik = $this->generateNik($created, $dusun->id);
            }

            Penduduk::query()->create([
                'nik' => $nik,
                'nama_lengkap' => fake()->name(),
                'tempat_lahir' => fake()->city(),
                'tanggal_lahir' => fake()->dateTimeBetween('-75 years', '-1 years')->format('Y-m-d'),
                'jenis_kelamin' => fake()->randomElement(['Laki-laki', 'Perempuan']),
                'agama' => fake()->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu']),
                'golongan_darah' => fake()->randomElement(['A', 'B', 'AB', 'O']),
                'status_perkawinan' => fake()->randomElement(['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati']),
                'pendidikan_id' => fake()->randomElement($pendidikanIds),
                'pekerjaan_id' => fake()->randomElement($pekerjaanIds),
                'kewarganegaraan' => 'WNI',
                'nama_ayah' => fake()->name('male'),
                'nama_ibu' => fake()->name('female'),
                'telepon' => fake()->numerify('08##########'),
                'email' => fake()->unique()->safeEmail(),
                'alamat' => fake()->streetAddress(),
                'dusun_id' => $dusun->id,
                'rw_id' => $rw->id,
                'rt_id' => $rt->id,
                'status_penduduk' => fake()->randomElement(['aktif', 'sementara', 'pindah', 'meninggal']),
                'masih_hidup' => fake()->boolean(90),
                'foto' => null,
            ]);

            $created++;
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

    protected function generateNik(int $index, int $dusunId): string
    {
        $prefix = str_pad((string) ($dusunId % 1000), 3, '0', STR_PAD_LEFT);
        $suffix = str_pad((string) ($index + 1), 7, '0', STR_PAD_LEFT);

        return '730801' . $prefix . $suffix;
    }
}
