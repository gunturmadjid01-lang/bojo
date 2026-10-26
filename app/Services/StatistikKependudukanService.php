<?php

namespace App\Services;

use App\Models\{AnggotaKartuKeluarga, AnggotaKedatangan, AnggotaPerpindahan, DisabilitasPenduduk, Dusun, KartuKeluarga, KedatanganPenduduk, Kelahiran, Kematian, PenerimaBantuan, Pendidikan, Pekerjaan, Penduduk, PendudukPindah, PenyaluranBantuan, PerpindahanPenduduk, RT, RW, SosialEkonomiKeluarga, SosialEkonomiPenduduk, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatistikKependudukanService
{
    public const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function for(string $module, User $user, ?int $year = null): array
    {
        $year ??= now()->year;

        return app(WilayahAccess::class)->forUser($user, function () use ($module, $year): array {
            return match ($module) {
                'kartu-keluarga' => $this->kartuKeluarga($year),

                'penduduk' => $this->penduduk($year),
                'kelahiran' => $this->kelahiran($year),
                'kematian' => $this->kematian($year),
                'penduduk-datang' => $this->pendudukDatang($year),
                'penduduk-pindah' => $this->pendudukPindah($year),
                default => [],
            } + [
                'year' => $year,
                'months' => self::MONTHS,
                'regions' => $this->allRegions(),
            ];
        });
    }

    public function years(): array
    {
        return [now()->year, now()->year - 1];
    }

    public function indexPayload(string $module, Request $request): array
    {
        $years = $this->years();
        $requestedYear = (int) $request->integer('statistik_tahun', now()->year);
        $year = in_array($requestedYear, $years, true) ? $requestedYear : now()->year;

        return [
            'statistics' => $this->for($module, $request->user(), $year),
            'statisticYears' => $years,
            'statisticYear' => $year,
        ];
    }

    private function kartuKeluarga(int $year): array
    {
        $active = KartuKeluarga::query()->where('status', 'aktif');
        $activeMembers = AnggotaKartuKeluarga::query()->where('aktif', true);

        return [
            'cards' => [
                ['label' => 'Total KK Aktif', 'value' => (clone $active)->count()],
                // ['label' => 'Total Anggota KK Aktif', 'value' => (clone $activeMembers)->whereHas('kartuKeluarga', fn ($q) => $q->where('status', 'aktif'))->count()],
                ['label' => 'KK Baru Tahun Ini', 'value' => (clone $active)->whereYear('tanggal_diterbitkan', $year)->count()],
            ],
            'monthly' => $this->monthly((clone $active), 'tanggal_diterbitkan', $year),
            'monthlyTitle' => 'KK Baru per Bulan',
            'regionTitle' => $this->regionTitle('Jumlah Kartu Keluarga'),
            'regionValues' => $this->regionValues((clone $active), 'kartu_keluargas'),
        ];
    }



    private function penduduk(int $year): array
    {
        $active = Penduduk::query()->where('status_penduduk', 'aktif')->where('masih_hidup', true);
        $productive = $this->ageQuery(clone $active, 15, 64);
        $elderly = (clone $active)->whereDate('tanggal_lahir', '<=', today()->subYears(60));
        $movedOut = PerpindahanPenduduk::query()->exists()
            ? AnggotaPerpindahan::query()->whereHas('perpindahan', fn ($query) => $query->whereYear('tanggal_pindah', $year))
            : PendudukPindah::query()->whereYear('tanggal_pindah', $year)->where('jenis_tujuan_kk', '!=', 'gabung_kk');
        $died = Kematian::query()->whereYear('tanggal_meninggal', $year);
        $arrived = AnggotaKedatangan::query()->whereHas('kedatanganPenduduk', fn ($query) => $query->whereYear('tanggal_datang', $year));
        $born = Kelahiran::query()->whereYear('tanggal_lahir', $year);

        return [
            'cards' => [
                ['label' => 'Total Penduduk Aktif', 'value' => (clone $active)->count()],
                ['label' => 'Laki-laki', 'value' => (clone $active)->where('jenis_kelamin', 'Laki-laki')->count()],
                ['label' => 'Perempuan', 'value' => (clone $active)->where('jenis_kelamin', 'Perempuan')->count()],
                ['label' => "Penduduk Masuk Tahun {$year}", 'value' => (clone $arrived)->count()],
                ['label' => "Penduduk Keluar Tahun {$year}", 'value' => (clone $movedOut)->count()],
                ['label' => "Kelahiran Tahun {$year}", 'value' => (clone $born)->count()],
                ['label' => "Kematian Tahun {$year}", 'value' => (clone $died)->count()],
                ['label' => 'Usia Produktif (15-64)', 'value' => $productive->count()],
                ['label' => 'Lansia (60+)', 'value' => $elderly->count()],
            ],
            'monthly' => [],
            'monthlyTitle' => null,
            'showMonthlyChart' => false,
            'regionTitle' => 'Jumlah Penduduk per Dusun',
            'regionValues' => $this->pendudukPerDusun(),
            'demographics' => [
                'Usia' => $this->ageGroups(clone $active),
                'Kelamin' => $this->groupedString(clone $active, 'jenis_kelamin'),
                'Goldar' => $this->groupedString(clone $active, 'golongan_darah'),
                'Agama' => $this->groupedString(clone $active, 'agama'),
                'Pendidikan' => $this->groupedMaster(clone $active, Pendidikan::class, 'pendidikan_id'),
                'Pekerjaan' => $this->groupedMaster(clone $active, Pekerjaan::class, 'pekerjaan_id', 10),
                'Status Perkawinan' => $this->groupedString(clone $active, 'status_perkawinan')
            ],
            'demographicsByRegion' => $this->pendudukDemographicsByRegion(),
        ];
    }

    private function pendudukDemographicsByRegion(): array
    {
        $rows = Penduduk::query()->with(['dusun:id,nama', 'pendidikan:id,nama', 'pekerjaan:id,nama'])
            ->where('status_penduduk', 'aktif')->where('masih_hidup', true)->get();
        $groups = $rows->groupBy(fn ($row) => $row->dusun?->nama ?: 'Belum Diketahui');
        $dimensions = ['gender' => [], 'education' => [], 'occupation' => [], 'age' => []];
        foreach ($groups as $region => $people) {
            $dimensions['gender'][$region] = $people->groupBy(fn ($row) => $row->jenis_kelamin ?: 'Belum Diketahui')->map->count()->all();
            $dimensions['education'][$region] = $people->groupBy(fn ($row) => $row->pendidikan?->nama ?: 'Belum Diketahui')->map->count()->all();
            $dimensions['occupation'][$region] = $people->groupBy(fn ($row) => $row->pekerjaan?->nama ?: 'Belum Diketahui')->map->count()->all();
            $dimensions['age'][$region] = $people->groupBy(fn ($row) => $this->ageBand($row->tanggal_lahir))->map->count()->all();
        }
        return $dimensions;
    }

    private function ageBand($birthDate): string
    {
        if (!$birthDate) return 'Belum Diketahui';
        $age = Carbon::parse($birthDate)->age;
        return match (true) { $age <= 5 => '0-5 tahun', $age <= 12 => '6-12 tahun', $age <= 17 => '13-17 tahun', $age <= 25 => '18-25 tahun', $age <= 40 => '26-40 tahun', $age <= 59 => '41-59 tahun', default => '60+ tahun' };
    }

    private function ageQuery(Builder $query, int $minimum, ?int $maximum = null): Builder
    {
        $latestBirth = today()->subYears($minimum);
        $query->whereNotNull('tanggal_lahir')->whereDate('tanggal_lahir', '<=', $latestBirth);

        if ($maximum !== null) {
            $query->whereDate('tanggal_lahir', '>', today()->subYears($maximum + 1));
        }

        return $query;
    }

    private function ageGroups(Builder $active): array
    {
        $groups = [
            ['label' => '0-5 tahun', 'value' => $this->ageQuery(clone $active, 0, 5)->count()],
            ['label' => '6-12 tahun', 'value' => $this->ageQuery(clone $active, 6, 12)->count()],
            ['label' => '13-17 tahun', 'value' => $this->ageQuery(clone $active, 13, 17)->count()],
            ['label' => '18-25 tahun', 'value' => $this->ageQuery(clone $active, 18, 25)->count()],
            ['label' => '26-40 tahun', 'value' => $this->ageQuery(clone $active, 26, 40)->count()],
            ['label' => '41-59 tahun', 'value' => $this->ageQuery(clone $active, 41, 59)->count()],
            ['label' => '60+ tahun', 'value' => $this->ageQuery(clone $active, 60)->count()],
        ];

        $known = collect($groups)->sum('value');
        return [...$groups, ['label' => 'Belum Diketahui', 'value' => max(0, $active->count() - $known)]];
    }

    private function groupedString(Builder $query, string $column): array
    {
        return $query->selectRaw("{$column} AS raw_label, COUNT(*) AS value")
            ->groupBy($column)
            ->get()
            ->map(fn ($row) => [
                'label' => filled(trim((string) $row->raw_label)) ? trim((string) $row->raw_label) : 'Belum Diketahui',
                'value' => (int) $row->value,
            ])
            ->groupBy('label')
            ->map(fn ($items, string $label) => ['label' => $label, 'value' => $items->sum('value')])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    private function groupedMaster(Builder $query, string $master, string $foreignKey, ?int $limit = null): array
    {
        $rows = $query->selectRaw("{$foreignKey} AS master_id, COUNT(*) AS value")
            ->groupBy($foreignKey)->get()->keyBy('master_id');
        $items = $master::query()->orderBy('nama')->get(['id', 'nama'])->map(fn ($item) => [
            'label' => $item->nama,
            'value' => (int) ($rows[$item->id]->value ?? 0),
        ])->sortByDesc('value')->values();
        $unknown = (int) ($rows[null]->value ?? 0);

        if ($limit !== null && $items->count() > $limit) {
            $top = $items->take($limit);
            $other = $items->skip($limit)->sum('value');
            $items = $top->push(['label' => 'Lainnya', 'value' => $other]);
        }

        if ($unknown > 0) $items->push(['label' => 'Belum Diketahui', 'value' => $unknown]);
        return $items->all();
    }

    private function pendudukPerDusun(): array
    {
        $access = app(WilayahAccess::class);
        $assignments = $access->assignments();
        $desaIds = $access->unrestricted()
            ? Dusun::withoutGlobalScope('wilayah')->distinct()->pluck('desa_id')
            : $assignments->pluck('desa_id')->filter()->unique()->values();

        if ($desaIds->isEmpty()) return [];

        return app(WilayahAccess::class)->forSystem(function () use ($desaIds): array {
            $dusuns = Dusun::withoutGlobalScope('wilayah')->where('aktif', true)->whereIn('desa_id', $desaIds)->orderBy('nama')->get(['id', 'nama']);
            $rows = Penduduk::withoutGlobalScopes()->where('status_penduduk', 'aktif')->where('masih_hidup', true)
                ->whereIn('dusun_id', $dusuns->pluck('id'))->selectRaw('dusun_id, COUNT(*) AS value')->groupBy('dusun_id')->pluck('value', 'dusun_id');

            return $dusuns->map(fn ($dusun) => ['label' => $dusun->nama, 'value' => (int) ($rows[$dusun->id] ?? 0)])->all();
        });
    }

    private function kelahiran(int $year): array
    {
        $base = Kelahiran::query();
        $yearQuery = (clone $base)->whereYear('tanggal_lahir', $year);

        return [
            'cards' => [
                ['label' => "Total Kelahiran Tahun {$year}", 'value' => (clone $yearQuery)->count()],
                ['label' => 'Kelahiran Bulan Ini', 'value' => (clone $yearQuery)->whereMonth('tanggal_lahir', now()->month)->count()],
                ['label' => 'Bayi Laki-laki', 'value' => (clone $yearQuery)->whereHas('penduduk', fn ($q) => $q->where('jenis_kelamin', 'Laki-laki'))->count()],
                ['label' => 'Bayi Perempuan', 'value' => (clone $yearQuery)->whereHas('penduduk', fn ($q) => $q->where('jenis_kelamin', 'Perempuan'))->count()],

            ],
            'monthly' => $this->monthly($base, 'tanggal_lahir', $year),
            'monthlyTitle' => 'Kelahiran per Bulan',
            'regionTitle' => $this->regionTitle('Jumlah Kelahiran'),
            'regionValues' => $this->regionValues($yearQuery, 'kelahirans', 'kartu_keluargas'),
        ];
    }

    private function kematian(int $year): array
    {
        $base = Kematian::query();
        $yearQuery = (clone $base)->whereYear('tanggal_meninggal', $year);
        $needsHead = KartuKeluarga::query()->where('status', 'aktif')->whereDoesntHave('anggotaKeluarga', fn ($q) => $q->where('aktif', true)->where('hubungan_keluarga', 'Kepala Keluarga'))->count();

        return [
            'cards' => [
                ['label' => "Total Kematian Tahun {$year}", 'value' => (clone $yearQuery)->count()],
                ['label' => 'Kematian Bulan Ini', 'value' => (clone $yearQuery)->whereMonth('tanggal_meninggal', now()->month)->count()],
                ['label' => 'Laki-laki', 'value' => (clone $yearQuery)->whereHas('penduduk', fn ($q) => $q->where('jenis_kelamin', 'Laki-laki'))->count()],
                ['label' => 'Perempuan', 'value' => (clone $yearQuery)->whereHas('penduduk', fn ($q) => $q->where('jenis_kelamin', 'Perempuan'))->count()],

            ],
            'monthly' => $this->monthly($base, 'tanggal_meninggal', $year),
            'monthlyTitle' => 'Kematian per Bulan',
            'regionTitle' => $this->regionTitle('Jumlah Kematian'),
            'regionValues' => $this->regionValues($yearQuery, 'kematians', 'penduduks'),
        ];
    }

    private function pendudukDatang(int $year): array
    {
        $events = KedatanganPenduduk::query();
        $yearEvents = (clone $events)->whereYear('tanggal_datang', $year);
        $members = AnggotaKedatangan::query()->whereHas('kedatanganPenduduk', fn ($q) => $q->whereYear('tanggal_datang', $year));
        $memberRows = (clone $members)->join('kedatangan_penduduks as kd', 'kd.id', '=', 'anggota_kedatangan.kedatangan_penduduk_id');

        return [
            'cards' => [
                ['label' => "Penduduk Masuk Tahun {$year}", 'value' => (clone $members)->count()],
                ['label' => 'Penduduk Masuk Bulan Ini', 'value' => (clone $members)->whereHas('kedatanganPenduduk', fn ($q) => $q->whereMonth('tanggal_datang', now()->month)->whereYear('tanggal_datang', $year))->count()],
            ],
            'monthly' => $this->monthly((clone $memberRows), 'kd.tanggal_datang', $year),
            'monthlyTitle' => 'Jumlah Penduduk Masuk per Bulan',
            'regionTitle' => $this->regionTitle('Penduduk Masuk'),
            'regionValues' => $this->regionValues((clone $memberRows), 'anggota_kedatangan', 'kartu_keluargas'),
        ];
    }

    private function pendudukPindah(int $year): array
    {
        if (!PerpindahanPenduduk::query()->exists()) {
            return $this->legacyPendudukPindah($year);
        }
        $base = PerpindahanPenduduk::query();
        $yearQuery = (clone $base)->whereYear('tanggal_pindah', $year);
        $members = AnggotaPerpindahan::query()->whereHas('perpindahan', fn ($query) => $query->whereYear('tanggal_pindah', $year));

        return [
            'cards' => [
                ['label' => "Total Penduduk Keluar Tahun {$year}", 'value' => (clone $members)->count()],
                ['label' => 'Penduduk Keluar Bulan Ini', 'value' => (clone $members)->whereHas('perpindahan', fn ($q) => $q->whereMonth('tanggal_pindah', now()->month)->whereYear('tanggal_pindah', $year))->count()],
            ],
            'monthly' => $this->monthly($base, 'tanggal_pindah', $year),
            'monthlyTitle' => 'Jumlah Penduduk Keluar per Bulan',
            'regionTitle' => $this->regionTitle('Jumlah Penduduk Keluar'),
            'regionValues' => $this->regionValues($yearQuery, 'penduduk_pindahs', 'penduduks'),
        ];
    }

    private function legacyPendudukPindah(int $year): array
    {
        $base = PendudukPindah::query();
        $yearQuery = (clone $base)->whereYear('tanggal_pindah', $year);

        return [
            'cards' => [
                ['label' => "Total Penduduk Keluar Tahun {$year}", 'value' => (clone $yearQuery)->count()],
            ],
            'monthly' => $this->monthly($base, 'tanggal_pindah', $year),
            'monthlyTitle' => 'Jumlah Penduduk Keluar per Bulan',
            'regionTitle' => 'Jumlah Penduduk Keluar per Dusun',
            'regionValues' => [],
        ];
    }

    private function sosialEkonomiKeluarga(int $year): array
    {
        $base = SosialEkonomiKeluarga::query();
        return $this->categoricalStatistics($base, $year, 'tanggal_pendataan', [
            'kategori_ekonomi' => 'Kategori Ekonomi Keluarga',
            'status_kepemilikan_rumah' => 'Status Kepemilikan Rumah',
            'kondisi_rumah' => 'Kondisi Rumah',
        ], 'Pendataan Sosial Ekonomi Keluarga per Bulan');
    }

    private function sosialEkonomiPenduduk(int $year): array
    {
        $base = SosialEkonomiPenduduk::query();
        return $this->categoricalStatistics($base, $year, 'tanggal_pendataan', [
            'status_pekerjaan' => 'Status Pekerjaan Penduduk',
            'kategori_penghasilan' => 'Kategori Penghasilan',
            'jaminan_kesehatan' => 'Jaminan Kesehatan',
        ], 'Pendataan Sosial Ekonomi Penduduk per Bulan');
    }

    private function disabilitas(int $year): array
    {
        $base = DisabilitasPenduduk::query();
        $cards = [
            ['label' => 'Total Penduduk Disabilitas', 'value' => (clone $base)->distinct('penduduk_id')->count('penduduk_id')],
            ['label' => 'Data Disabilitas', 'value' => (clone $base)->count()],
        ];
        return $this->categoricalStatistics($base, $year, null, [
            'tingkat' => 'Tingkat Disabilitas',
        ], 'Data Disabilitas per Bulan', $cards, 'jenisDisabilitas', 'jenis_disabilitas_id');
    }

    private function penerimaBantuan(int $year): array
    {
        $base = PenerimaBantuan::query();
        return $this->categoricalStatistics($base, $year, 'tanggal_terdaftar', [
            'status_kelayakan' => 'Status Kelayakan Penerima',
            'status_verifikasi' => 'Status Verifikasi Penerima',
        ], 'Penerima Bantuan Terdaftar per Bulan', null, 'program', 'program_bantuan_id');
    }

    private function penyaluranBantuan(int $year): array
    {
        $base = PenyaluranBantuan::query();
        $cards = [
            ['label' => 'Total Penyaluran', 'value' => (clone $base)->count()],
            ['label' => 'Total Nilai Bantuan', 'value' => (float) ((clone $base)->sum('jumlah'))],
            ['label' => 'Sudah Disalurkan', 'value' => (clone $base)->where('status', 'disalurkan')->count()],
        ];
        return $this->categoricalStatistics($base, $year, 'tanggal_penyaluran', [
            'status' => 'Status Penyaluran',
            'periode' => 'Periode Penyaluran',
        ], 'Penyaluran Bantuan per Bulan', $cards, 'penerima.program', null);
    }

    private function categoricalStatistics(Builder $base, int $year, ?string $dateColumn, array $dimensions, string $monthlyTitle, ?array $cards = null, ?string $relation = null, ?string $relationKey = null): array
    {
        $cards ??= [['label' => 'Total Data', 'value' => (clone $base)->count()]];
        $demographics = [];
        foreach ($dimensions as $column => $title) {
            $demographics[$column] = $this->groupedString(clone $base, $column);
        }
        if ($relation && $relationKey) {
            $demographics['relation'] = $this->groupedRelation(clone $base, $relation, $relationKey);
        }
        return [
            'cards' => $cards,
            'monthly' => $dateColumn ? $this->monthly((clone $base), $dateColumn, $year) : array_fill(0, 12, 0),
            'monthlyTitle' => $monthlyTitle,
            'regionTitle' => 'Jumlah Data per Dusun',
            'regionValues' => $this->categoricalRegionValues($base, $base->getModel()->getTable()),
            'demographics' => $demographics,
        ];
    }

    private function groupedRelation(Builder $query, string $relation, string $foreignKey): array
    {
        $relationName = str_contains($relation, '.') ? str($relation)->beforeLast('.')->toString() : $relation;
        $labelRelation = str($relation)->afterLast('.')->toString();
        return $query->with($relation)->get()->groupBy(fn ($row) => data_get($row, $relation.'.nama') ?? 'Belum Diketahui')
            ->map(fn ($rows, $label) => ['label' => $label, 'value' => $rows->count()])->sortByDesc('value')->values()->all();
    }

    private function regionRelation(string $table): ?string
    {
        return match ($table) {
            'sosial_ekonomi_keluargas' => 'kartu_keluargas',
            'sosial_ekonomi_penduduks', 'disabilitas_penduduks' => 'penduduks',
            'penerima_bantuans' => 'penduduks',
            'penyaluran_bantuans' => 'penerima_bantuans',
            default => null,
        };
    }

    private function categoricalRegionValues(Builder $base, string $table): array
    {
        if ($table === 'penyaluran_bantuans') {
            $regions = $this->allRegions();
            if (!$regions) return [];
            return app(WilayahAccess::class)->forSystem(function () use ($base, $regions): array {
                $query = (clone $base)->withoutGlobalScopes()
                    ->leftJoin('penerima_bantuans as pb', 'pb.id', '=', 'penyaluran_bantuans.penerima_bantuan_id')
                    ->leftJoin('penduduks as pp', 'pp.id', '=', 'pb.penduduk_id')
                    ->leftJoin('kartu_keluargas as kk', 'kk.id', '=', 'pb.kartu_keluarga_id');
                $rows = $query->selectRaw('COALESCE(pp.dusun_id, kk.dusun_id) AS region_id, COUNT(*) AS total')
                    ->groupByRaw('COALESCE(pp.dusun_id, kk.dusun_id)')->pluck('total', 'region_id');
                return collect($regions)->map(fn ($region) => ['label' => $region['label'], 'value' => (int) ($rows[$region['id']] ?? 0)])->all();
            });
        }

        return $this->regionValues($base, $table, $this->regionRelation($table));
    }

    private function average(int $numerator, int $denominator): float
    {
        return $denominator > 0 ? round($numerator / $denominator, 2) : 0;
    }

    private function monthly(Builder $query, string $column, int $year): array
    {
        $values = array_fill(1, 12, 0);
        $expression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', {$column}) AS INTEGER)"
            : "MONTH({$column})";
        $rows = (clone $query)->whereYear($column, $year)
            ->selectRaw("{$expression} AS month_number, COUNT(*) AS total")
            ->groupByRaw($expression)
            ->pluck('total', 'month_number');

        foreach ($rows as $month => $total) $values[(int) $month] = (int) $total;

        return array_values($values);
    }

    private function allRegions(): array
    {
        $access = app(WilayahAccess::class);
        $desaIds = $access->unrestricted()
            ? Dusun::withoutGlobalScope('wilayah')->distinct()->pluck('desa_id')
            : $access->assignments()->pluck('desa_id')->filter()->unique()->values();

        return $access->forSystem(fn () => Dusun::withoutGlobalScope('wilayah')->where('aktif', true)->whereIn('desa_id', $desaIds)->orderBy('nama')->get(['id', 'nama'])->map(fn ($item) => ['id' => $item->id, 'label' => $item->nama, 'level' => 'dusun'])->all());
    }

    private function regionTitle(string $prefix): string
    {
        $regions = $this->allRegions();
        $level = $regions[0]['level'] ?? 'dusun';
        return $prefix.' per '.($level === 'dusun' ? 'Dusun' : strtoupper($level));
    }

    private function regionValues(Builder $query, string $baseTable, ?string $relationTable = null): array
    {
        $regions = $this->allRegions();
        if (!$regions) return [];
        $level = $regions[0]['level'];
        $column = $level === 'dusun' ? 'dusun_id' : ($level === 'rw' ? 'rw_id' : 'rt_id');
        $dimensionTable = $level === 'dusun' ? 'dusuns' : ($level === 'rw' ? 'r_w_s' : 'r_t_s');
        return app(WilayahAccess::class)->forSystem(function () use ($query, $baseTable, $relationTable, $regions, $level, $dimensionTable, $column): array {
            $query = clone $query->withoutGlobalScopes();

        if ($relationTable === 'kartu_keluargas') {
            if (in_array($baseTable, ['anggota_kartu_keluargas', 'kelahirans', 'sosial_ekonomi_keluargas'], true)) {
                $query->join('kartu_keluargas as kk', 'kk.id', '=', $baseTable.'.kartu_keluarga_id');
            } elseif ($baseTable === 'anggota_kedatangan') {
                $query->join('kartu_keluargas as kk', 'kk.id', '=', 'kd.kartu_keluarga_tujuan_id');
            }
            $dimensionSource = 'kk';
        } elseif ($relationTable === 'penduduks') {
            $query->join('penduduks as pp', 'pp.id', '=', $baseTable.'.penduduk_id');
            $dimensionSource = 'pp';
        } elseif ($relationTable === 'kedatangan_penduduks') {
            $query->join('kedatangan_penduduks as kd', 'kd.id', '=', $baseTable.'.kedatangan_penduduk_id')
                ->join('kartu_keluargas as kk', 'kk.id', '=', 'kd.kartu_keluarga_tujuan_id');
            $dimensionSource = 'kk';
        } else {
            $dimensionSource = $baseTable;
        }

        $dimension = $dimensionSource.'.'.$column;

        $rows = $query->selectRaw("{$dimension} AS region_id, COUNT(*) AS total")
            ->groupBy($dimension)->pluck('total', 'region_id');

            return collect($regions)->map(fn ($region) => ['label' => $region['label'], 'value' => (int) ($rows[$region['id']] ?? 0)])->all();
        });
    }
}
