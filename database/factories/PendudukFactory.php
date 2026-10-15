<?php

namespace Database\Factories;

use App\Models\Dusun;
use App\Models\Pendidikan;
use App\Models\Pekerjaan;
use App\Models\Penduduk;
use App\Models\RT;
use App\Models\RW;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Penduduk>
 */
class PendudukFactory extends Factory
{
    protected $model = Penduduk::class;

    public function definition(): array
    {
        $dusun = Dusun::query()->inRandomOrder()->first();
        $rw = $dusun ? RW::query()->where('dusun_id', $dusun->id)->inRandomOrder()->first() : null;
        $rt = $rw ? RT::query()->where('rw_id', $rw->id)->inRandomOrder()->first() : null;

        $tanggalLahir = $this->faker->dateTimeBetween('-75 years', '-17 years')->format('Y-m-d');
        $jenisKelamin = $this->faker->randomElement(['Laki-laki', 'Perempuan']);
        $agama = $this->faker->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu']);
        $statusPerkawinan = $this->faker->randomElement(['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati']);
        $pendidikan = Pendidikan::query()->inRandomOrder()->first();
        $pekerjaan = Pekerjaan::query()->inRandomOrder()->first();

        return [
            'nik' => $this->faker->unique()->numerify('730801###' . str_repeat('#', 7)),
            'nama_lengkap' => $this->faker->name($jenisKelamin === 'Laki-laki' ? 'male' : 'female'),
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $tanggalLahir,
            'jenis_kelamin' => $jenisKelamin,
            'agama' => $agama,
            'golongan_darah' => $this->faker->randomElement(['A', 'B', 'AB', 'O']),
            'status_perkawinan' => $statusPerkawinan,
            'pendidikan_id' => $pendidikan?->id,
            'pekerjaan_id' => $pekerjaan?->id,
            'kewarganegaraan' => 'WNI',
            'nama_ayah' => $this->faker->name('male'),
            'nama_ibu' => $this->faker->name('female'),
            'telepon' => $this->faker->optional()->numerify('08##########'),
            'email' => $this->faker->optional()->safeEmail(),
            'alamat' => $this->faker->streetAddress(),
            'dusun_id' => $dusun?->id,
            'rw_id' => $rw?->id,
            'rt_id' => $rt?->id,
            'status_penduduk' => $this->faker->randomElement(['aktif', 'sementara', 'pindah', 'meninggal']),
            'masih_hidup' => $this->faker->boolean(90),
            'foto' => null,
        ];
    }
}
