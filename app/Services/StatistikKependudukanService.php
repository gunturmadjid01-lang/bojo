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

    public function for(string $module, User $user, ?int $year = null, bool $includeRecaps = false): array
    {
        $year ??= now()->year;

        return app(WilayahAccess::class)->forUser($user, function () use ($module, $year, $includeRecaps): array {
            return match ($module) {
                'kartu-keluarga' => $this->kartuKeluarga($year),

                'penduduk' => $this->penduduk($year, $includeRecaps),
                'kelahiran' => $this->kelahiran($year, $includeRecaps),
                'kematian' => $this->kematian($year, $includeRecaps),
                'penduduk-datang' => $this->pendudukDatang($year, $includeRecaps),
                'penduduk-pindah' => $this->pendudukPindah($year, $includeRecaps),
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
            'statistics' => $this->for($module, $request->user(), $year, in_array($module, ['penduduk', 'kelahiran', 'kematian', 'penduduk-datang', 'penduduk-pindah'], true)),
            'statisticYears' => $years,
            'statisticYear' => $year,
        ];
    }

    private function kartuKeluarga(int $year): array
    {
        $active = KartuKeluarga::query()->where('status', 'aktif');
        $activeMembers = AnggotaKartuKeluarga::query()->where('aktif', true);
        $perDusun = $this->regionValues((clone $active), 'kartu_keluargas');
        $monthly = $this->monthly((clone $active), 'tanggal_diterbitkan', $year);
        $membersBySize = DB::table('kartu_keluargas as kk')
            ->leftJoin('anggota_kartu_keluargas as anggota', function ($join) {
                $join->on('anggota.kartu_keluarga_id', '=', 'kk.id')->where('anggota.aktif', true);
            })
            ->where('kk.status', 'aktif')
            ->groupBy('kk.id')
            ->selectRaw('COUNT(anggota.id) AS jumlah_anggota')
            ->get()
            ->groupBy(fn ($row) => (int) $row->jumlah_anggota)
            ->map(fn ($rows, $count) => ['label' => "{$count} orang", 'value' => $rows->count()])
            ->values()->all();
        $dusunRows = collect($perDusun)->map(fn (array $row) => ['label' => $row['label'], 'total' => (int) $row['value']])->values();
        $dusunRows->push(['label' => 'TOTAL', 'total' => $dusunRows->sum('total')]);
        $monthRows = collect(self::MONTHS)->values()->map(fn (string $month, int $index) => ['label' => $month, 'total' => (int) ($monthly[$index] ?? 0)]);
        $monthRows->push(['label' => 'TOTAL', 'total' => $monthRows->sum('total')]);
        $sizeRows = collect($membersBySize)->map(fn (array $row) => ['label' => $row['label'], 'total' => (int) $row['value']])->values();
        $sizeRows->push(['label' => 'TOTAL', 'total' => $sizeRows->sum('total')]);

        return [
            'cards' => [
                ['label' => 'Total KK Aktif', 'value' => (clone $active)->count()],
                ['label' => 'Total Anggota KK Aktif', 'value' => (clone $activeMembers)->whereHas('kartuKeluarga', fn ($q) => $q->where('status', 'aktif'))->count()],
                ['label' => 'KK Baru Tahun Ini', 'value' => (clone $active)->whereYear('tanggal_diterbitkan', $year)->count()],
            ],
            'summaryTitle' => 'Rekap Total Kartu Keluarga',
            'rekapTables' => [[
                'title' => 'Rekap Kartu Keluarga Per Dusun',
                'columns' => ['Dusun', 'Jumlah KK'],
                'rows' => $dusunRows->all(),
            ]],
            'grafikTables' => [
                ['title' => 'Rekap KK Baru Per Bulan', 'columns' => ['Bulan', 'Jumlah KK'], 'rows' => $monthRows->all()],
                ['title' => 'Rekap KK Aktif Menurut Jumlah Anggota', 'columns' => ['Jumlah Anggota', 'Jumlah KK'], 'rows' => $sizeRows->all()],
            ],
            'monthly' => $monthly,
            'monthlyTitle' => 'Jumlah KK Baru per Bulan',
            'showMonthlyChart' => false,
            'regionTitle' => $this->regionTitle('Jumlah Kartu Keluarga'),
            'regionValues' => $perDusun,
        ];
    }



    private function penduduk(int $year, bool $includeRecaps = false): array
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
                'Jenis Kelamin' => $this->groupedString(clone $active, 'jenis_kelamin'),
                'Goldar' => $this->groupedString(clone $active, 'golongan_darah'),
                'Agama' => $this->groupedString(clone $active, 'agama'),
                'Pendidikan' => $this->groupedMaster(clone $active, Pendidikan::class, 'pendidikan_id'),
                'Pekerjaan' => $this->groupedMaster(clone $active, Pekerjaan::class, 'pekerjaan_id', 10),
                'Status Perkawinan' => $this->groupedString(clone $active, 'status_perkawinan')
            ],
            'demographicsByRegion' => $this->pendudukDemographicsByRegion(),
            ...($includeRecaps ? $this->pendudukRecapTables() : []),
        ];
    }

    private function eventTables(string $label, array $perDusun, array $monthly, array $gender): array
    {
        $dusunRows = collect($perDusun)->map(fn (array $row) => ['label' => $row['label'], 'total' => (int) $row['value']])->values();
        $dusunRows->push(['label' => 'TOTAL', 'total' => $dusunRows->sum('total')]);
        $monthRows = collect(self::MONTHS)->values()->map(fn (string $month, int $index) => [
            'label' => $month,
            'total' => (int) ($monthly[$index] ?? 0),
        ]);
        $monthRows->push(['label' => 'TOTAL', 'total' => $monthRows->sum('total')]);
        $genderRows = collect($gender)->map(fn (array $row) => ['label' => $row['label'], 'total' => (int) $row['value']])->values();
        $genderRows->push(['label' => 'TOTAL', 'total' => $genderRows->sum('total')]);

        return [
            'summaryTitle' => "Rekap Total {$label}",
            'rekapTables' => [[
                'title' => "Rekap {$label} Per Dusun",
                'columns' => ['Dusun', "Jumlah {$label}"],
                'rows' => $dusunRows->all(),
            ]],
            'grafikTables' => [
                ['title' => "Rekap {$label} Per Bulan", 'columns' => ['Bulan', "Jumlah {$label}"], 'rows' => $monthRows->all()],
                ['title' => "Rekap {$label} Menurut Jenis Kelamin", 'columns' => ['Jenis Kelamin', "Jumlah {$label}"], 'rows' => $genderRows->all()],
            ],
        ];
    }

    private function genderCounts(Builder $query, string $baseTable): array
    {
        return (clone $query)
            ->join('penduduks as statistik_penduduk', 'statistik_penduduk.id', '=', $baseTable.'.penduduk_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(statistik_penduduk.jenis_kelamin), ''), 'Belum Diketahui') AS label, COUNT(*) AS value")
            ->groupBy('statistik_penduduk.jenis_kelamin')
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->label, 'value' => (int) $row->value])
            ->all();
    }

    private function pendudukRecapTables(): array
    {
        $people = Penduduk::query()
            ->with(['dusun:id,nama', 'pendidikan:id,nama', 'pekerjaan:id,nama'])
            ->where('status_penduduk', 'aktif')
            ->where('masih_hidup', true)
            ->get();
        $dusuns = Dusun::query()->where('aktif', true)->orderBy('nama')->get(['id', 'nama']);
        $peopleByDusun = $people->groupBy(fn (Penduduk $person) => $person->dusun_id ? (int) $person->dusun_id : 'unknown');
        $groups = $dusuns->mapWithKeys(fn (Dusun $dusun) => [$dusun->id => $peopleByDusun->get($dusun->id, collect())]);
        if ($peopleByDusun->has('unknown')) $groups->put('unknown', $peopleByDusun->get('unknown'));
        $regionLabels = $dusuns->mapWithKeys(fn (Dusun $dusun) => [$dusun->id => $dusun->nama]);
        if ($peopleByDusun->has('unknown')) $regionLabels->put('unknown', 'Belum Diketahui');
        $columnsFor = fn (callable $resolve): array => $people->map(fn (Penduduk $person) => $resolve($person))
            ->unique()->sort()->values()->all();
        $crossTab = function (string $title, callable $resolve, ?array $preferredCategories = null) use ($people, $groups, $regionLabels, $columnsFor): array {
            $available = $columnsFor($resolve);
            $categories = $preferredCategories ? array_values(array_intersect($preferredCategories, $available)) : $available;
            $rows = $groups->map(function ($residents, $region) use ($categories, $resolve, $regionLabels): array {
                $row = ['label' => $regionLabels->get($region, 'Belum Diketahui')];
                foreach ($categories as $category) {
                    $row[$category] = $residents->filter(fn (Penduduk $person) => $resolve($person) === $category)->count();
                }
                $row['total'] = $residents->count();
                return $row;
            })->values()->all();
            $totalRow = ['label' => 'TOTAL'];
            foreach ($categories as $category) $totalRow[$category] = $people->filter(fn (Penduduk $person) => $resolve($person) === $category)->count();
            $totalRow['total'] = $people->count();

            return ['title' => $title, 'columns' => ['Dusun', ...$categories, 'Total'], 'rows' => [...$rows, $totalRow]];
        };
        $unknown = fn (?string $value): string => filled(trim((string) $value)) ? trim((string) $value) : 'Belum Diketahui';
        $ageGroup = fn (Penduduk $person): string => $person->tanggal_lahir
            ? $this->ageBand($person->tanggal_lahir)
            : 'Belum Diketahui';
        $totalByDusun = [
            'title' => 'Rekap Penduduk Per Dusun',
            'columns' => ['Dusun', 'Jumlah Penduduk'],
            'rows' => $groups->map(fn ($residents, $region) => ['label' => $regionLabels->get($region, 'Belum Diketahui'), 'total' => $residents->count()])->values()->all(),
        ];
        $totalByDusun['rows'][] = ['label' => 'TOTAL', 'total' => $people->count()];

        return [
            'rekapTables' => [$totalByDusun],
            'grafikTables' => [
                $crossTab('Rekap Jenis Kelamin Per Dusun', fn (Penduduk $person) => $unknown($person->jenis_kelamin)),
                $crossTab('Rekap Pendidikan Per Dusun', fn (Penduduk $person) => $unknown($person->pendidikan?->nama)),
                $crossTab('Rekap Pekerjaan Per Dusun', fn (Penduduk $person) => $unknown($person->pekerjaan?->nama)),
                $crossTab('Rekap Golongan Darah Per Dusun', fn (Penduduk $person) => $unknown($person->golongan_darah)),
                $crossTab('Rekap Agama Per Dusun', fn (Penduduk $person) => $unknown($person->agama)),
                $crossTab('Rekap Status Perkawinan Per Dusun', fn (Penduduk $person) => $unknown($person->status_perkawinan)),
                $crossTab('Rekap Kelompok Usia Per Dusun', $ageGroup, ['0-5 tahun', '6-12 tahun', '13-17 tahun', '18-25 tahun', '26-40 tahun', '41-59 tahun', '60+ tahun', 'Belum Diketahui']),
            ],
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
        $dusuns = Dusun::query()->where('aktif', true)->orderBy('nama')->get(['id', 'nama']);
        if ($dusuns->isEmpty()) return [];
        $rows = Penduduk::query()->where('status_penduduk', 'aktif')->where('masih_hidup', true)
            ->whereIn('dusun_id', $dusuns->pluck('id'))->selectRaw('dusun_id, COUNT(*) AS value')->groupBy('dusun_id')->pluck('value', 'dusun_id');

        return $dusuns->map(fn ($dusun) => ['label' => $dusun->nama, 'value' => (int) ($rows[$dusun->id] ?? 0)])->all();
    }

    private function kelahiran(int $year, bool $includeRecaps = false): array
    {
        $base = Kelahiran::query();
        $yearQuery = (clone $base)->whereYear('tanggal_lahir', $year);
        $monthly = $this->monthly($base, 'tanggal_lahir', $year);
        $perDusun = $this->regionValues($yearQuery, 'kelahirans', 'kartu_keluargas');

        return [
            'cards' => [
                ['label' => "Total Kelahiran Tahun {$year}", 'value' => (clone $yearQuery)->count()],
                ['label' => 'Kelahiran Bulan Ini', 'value' => (clone $yearQuery)->whereMonth('tanggal_lahir', now()->month)->count()],
                ['label' => 'Bayi Laki-laki', 'value' => (clone $yearQuery)->whereHas('penduduk', fn ($q) => $q->where('jenis_kelamin', 'Laki-laki'))->count()],
                ['label' => 'Bayi Perempuan', 'value' => (clone $yearQuery)->whereHas('penduduk', fn ($q) => $q->where('jenis_kelamin', 'Perempuan'))->count()],

            ],
            'monthly' => $monthly,
            'monthlyTitle' => 'Kelahiran per Bulan',
            'regionTitle' => $this->regionTitle('Jumlah Kelahiran'),
            'regionValues' => $perDusun,
            ...($includeRecaps ? $this->eventTables('Kelahiran', $perDusun, $monthly, $this->genderCounts($yearQuery, 'kelahirans')) : []),
        ];
    }

    private function kematian(int $year, bool $includeRecaps = false): array
    {
        $base = Kematian::query();
        $yearQuery = (clone $base)->whereYear('tanggal_meninggal', $year);
        $monthly = $this->monthly($base, 'tanggal_meninggal', $year);
        $perDusun = $this->regionValues($yearQuery, 'kematians', 'penduduks');
        $needsHead = KartuKeluarga::query()->where('status', 'aktif')->whereDoesntHave('anggotaKeluarga', fn ($q) => $q->where('aktif', true)->where('hubungan_keluarga', 'Kepala Keluarga'))->count();

        return [
            'cards' => [
                ['label' => "Total Kematian Tahun {$year}", 'value' => (clone $yearQuery)->count()],
                ['label' => 'Kematian Bulan Ini', 'value' => (clone $yearQuery)->whereMonth('tanggal_meninggal', now()->month)->count()],
                ['label' => 'Laki-laki', 'value' => (clone $yearQuery)->whereHas('penduduk', fn ($q) => $q->where('jenis_kelamin', 'Laki-laki'))->count()],
                ['label' => 'Perempuan', 'value' => (clone $yearQuery)->whereHas('penduduk', fn ($q) => $q->where('jenis_kelamin', 'Perempuan'))->count()],

            ],
            'monthly' => $monthly,
            'monthlyTitle' => 'Kematian per Bulan',
            'regionTitle' => $this->regionTitle('Jumlah Kematian'),
            'regionValues' => $perDusun,
            ...($includeRecaps ? $this->eventTables('Kematian', $perDusun, $monthly, $this->genderCounts($yearQuery, 'kematians')) : []),
        ];
    }

    private function pendudukDatang(int $year, bool $includeRecaps = false): array
    {
        $events = KedatanganPenduduk::query();
        $yearEvents = (clone $events)->whereYear('tanggal_datang', $year);
        $members = AnggotaKedatangan::query()->whereHas('kedatanganPenduduk', fn ($q) => $q->whereYear('tanggal_datang', $year));
        $memberRows = (clone $members)->join('kedatangan_penduduks as kd', 'kd.id', '=', 'anggota_kedatangan.kedatangan_penduduk_id');
        $monthly = $this->monthly((clone $memberRows), 'kd.tanggal_datang', $year);
        $perDusun = $this->regionValues((clone $memberRows), 'anggota_kedatangan', 'kartu_keluargas');

        return [
            'cards' => [
                ['label' => "Penduduk Masuk Tahun {$year}", 'value' => (clone $members)->count()],
                ['label' => 'Penduduk Masuk Bulan Ini', 'value' => (clone $members)->whereHas('kedatanganPenduduk', fn ($q) => $q->whereMonth('tanggal_datang', now()->month)->whereYear('tanggal_datang', $year))->count()],
            ],
            'monthly' => $monthly,
            'monthlyTitle' => 'Jumlah Penduduk Masuk per Bulan',
            'regionTitle' => $this->regionTitle('Penduduk Masuk'),
            'regionValues' => $perDusun,
            ...($includeRecaps ? $this->eventTables('Penduduk Masuk', $perDusun, $monthly, $this->genderCounts($memberRows, 'anggota_kedatangan')) : []),
        ];
    }

    private function pendudukPindah(int $year, bool $includeRecaps = false): array
    {
        if (!PerpindahanPenduduk::query()->exists()) {
            return $this->legacyPendudukPindah($year, $includeRecaps);
        }
        $base = PerpindahanPenduduk::query();
        $yearQuery = (clone $base)->whereYear('tanggal_pindah', $year);
        $members = AnggotaPerpindahan::query()->whereHas('perpindahan', fn ($query) => $query->whereYear('tanggal_pindah', $year));
        $memberRows = (clone $members)->join('perpindahan_penduduks as pph', 'pph.id', '=', 'anggota_perpindahan.perpindahan_penduduk_id');
        $monthly = $this->monthly((clone $memberRows), 'pph.tanggal_pindah', $year);
        $perDusun = $this->regionValues((clone $memberRows), 'anggota_perpindahan', 'kartu_keluargas');

        return [
            'cards' => [
                ['label' => "Total Penduduk Pindah Tahun {$year}", 'value' => (clone $members)->count()],
                ['label' => 'Penduduk Pindah Bulan Ini', 'value' => (clone $members)->whereHas('perpindahan', fn ($q) => $q->whereMonth('tanggal_pindah', now()->month)->whereYear('tanggal_pindah', $year))->count()],
            ],
            'monthly' => $monthly,
            'monthlyTitle' => 'Jumlah Penduduk Pindah per Bulan',
            'regionTitle' => $this->regionTitle('Jumlah Penduduk Pindah'),
            'regionValues' => $perDusun,
            ...($includeRecaps ? $this->eventTables('Penduduk Keluar', $perDusun, $monthly, $this->genderCounts($memberRows, 'anggota_perpindahan')) : []),
        ];
    }

    private function legacyPendudukPindah(int $year, bool $includeRecaps = false): array
    {
        $base = PendudukPindah::query();
        $yearQuery = (clone $base)->whereYear('tanggal_pindah', $year);
        $monthly = $this->monthly($base, 'tanggal_pindah', $year);
        $perDusun = $this->regionValues($yearQuery, 'penduduk_pindahs', 'penduduks');

        return [
            'cards' => [
                ['label' => "Total Penduduk Pindah Tahun {$year}", 'value' => (clone $yearQuery)->count()],
            ],
            'monthly' => $monthly,
            'monthlyTitle' => 'Jumlah Penduduk Pindah per Bulan',
            'regionTitle' => 'Jumlah Penduduk Pindah per Dusun',
            'regionValues' => $perDusun,
            ...($includeRecaps ? $this->eventTables('Penduduk Keluar', $perDusun, $monthly, $this->genderCounts($yearQuery, 'penduduk_pindahs')) : []),
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
        return Dusun::query()->where('aktif', true)->orderBy('nama')->get(['id', 'nama'])
            ->map(fn ($item) => ['id' => $item->id, 'label' => $item->nama, 'level' => 'dusun'])
            ->all();
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
            } elseif ($baseTable === 'anggota_perpindahan') {
                $query->join('kartu_keluargas as kk', 'kk.id', '=', $baseTable.'.kartu_keluarga_asal_id');
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

        $allowedRegionIds = collect($regions)->pluck('id');
        $rows = $query->whereIn($dimension, $allowedRegionIds)->selectRaw("{$dimension} AS region_id, COUNT(*) AS total")
            ->groupBy($dimension)->pluck('total', 'region_id');

            return collect($regions)->map(fn ($region) => ['label' => $region['label'], 'value' => (int) ($rows[$region['id']] ?? 0)])->all();
        });
    }
}
