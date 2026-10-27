import MasterResourcePage from '@/components/master-resource-page';
import { router } from '@inertiajs/react';

const formatDate = (value) => {
    if (!value) return '-';

    return new Date(value).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatLabel = (value) => {
    if (!value) return '-';

    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const usiaTahun = (tanggalLahir) => {
    if (!tanggalLahir) return null;
    const [tahun, bulan, hari] = String(tanggalLahir).slice(0, 10).split('-').map(Number);
    if (!tahun || !bulan || !hari) return null;
    const sekarang = new Date();
    let usia = sekarang.getFullYear() - tahun;
    if (sekarang.getMonth() + 1 < bulan || (sekarang.getMonth() + 1 === bulan && sekarang.getDate() < hari)) usia -= 1;
    return usia >= 0 ? usia : null;
};

const rentangUsia = [
    { value: '0-5', label: '0–5 tahun' }, { value: '6-12', label: '6–12 tahun' },
    { value: '13-17', label: '13–17 tahun' }, { value: '18-25', label: '18–25 tahun' },
    { value: '26-59', label: '26–59 tahun' }, { value: '60+', label: '60 tahun ke atas' },
];

const formatRtRw = (item) => {
    const rt = item.rt?.nomor ? `RT ${item.rt.nomor}` : 'RT -';
    const rw = item.rw?.nomor ? `RW ${item.rw.nomor}` : 'RW -';

    return `${rt} / ${rw}`;
};

export default function PendudukIndex({ penduduks = [] }) {
    return (
        <MasterResourcePage
            title="Penduduk"
            group="Kependudukan"
            createHref="/penduduk/create"
            createLabel="Tambah Penduduk"
            data={penduduks}
            searchFields={[
                'nik',
                'nama_lengkap',
                'jenis_kelamin',
                'tempat_lahir',
                'status_perkawinan',
                'status_penduduk',
                (item) => item.pendidikan?.nama,
                (item) => item.pekerjaan?.nama,
                (item) => item.dusun?.nama,
                (item) => item.rt?.nomor,
                (item) => item.rw?.nomor,
            ]}
            searchPlaceholder="Cari penduduk..."
            extraFilters={[
                { key: 'golonganDarah', label: 'Golongan Darah', options: ['A', 'B', 'AB', 'O'], getValue: (item) => item.golongan_darah },
                { key: 'agama', label: 'Agama', getValue: (item) => item.agama },
                { key: 'pendidikan', label: 'Pendidikan', getValue: (item) => item.pendidikan?.nama },
                { key: 'pekerjaan', label: 'Pekerjaan', getValue: (item) => item.pekerjaan?.nama },
                { key: 'usia', label: 'Usia', options: rentangUsia, getValue: (item) => usiaTahun(item.tanggal_lahir) },
            ]}
            emptyMessage="Data penduduk tidak ditemukan."
            itemLabel={(item) => `${item.nama_lengkap} (${item.nik})`}
            detailHref={(item) => `/penduduk/${item.id}`}
            editHref={(item) => `/penduduk/${item.id}/edit`}
            onDelete={(item) => window.confirm(`Hapus penduduk ${item.nama_lengkap}?`) && router.delete(`/penduduk/${item.id}`)}
            columns={[
                {
                    name: 'Identitas',
                    selector: (item) => item.nama_lengkap || '-',
                    sortable: true,
                    grow: 1.5,
                    minWidth: '210px',
                    cell: (item) => (
                        <div className="py-2">
                            <p className="font-semibold text-slate-700">{item.nama_lengkap || '-'}</p>
                            <p className="mt-0.5 text-xs text-slate-500">NIK {item.nik || '-'}</p>
                        </div>
                    ),
                },
                {
                    name: 'Usia',
                    selector: (item) => usiaTahun(item.tanggal_lahir) === null ? '-' : `${usiaTahun(item.tanggal_lahir)} tahun`,
                    sortable: true,
                    minWidth: '100px',
                },
                {
                    name: 'Jenis Kelamin',
                    selector: (item) => item.jenis_kelamin || '-',
                    sortable: true,
                    minWidth: '135px',
                },
                {
                    name: 'Pekerjaan',
                    selector: (item) => item.pekerjaan?.nama || '-',
                    sortable: true,
                    minWidth: '160px',
                },
                {
                    name: 'Pendidikan',
                    selector: (item) => item.pendidikan?.nama || '-',
                    sortable: true,
                    minWidth: '150px',
                },
                {
                    name: 'Tempat, Tanggal Lahir',
                    selector: (item) =>
                        [item.tempat_lahir, formatDate(item.tanggal_lahir)].filter((value) => value && value !== '-').join(', ') || '-',
                    sortable: true,
                    minWidth: '210px',
                },
                {
                    name: 'Status Perkawinan',
                    selector: (item) => item.status_perkawinan || '-',
                    sortable: true,
                    minWidth: '160px',
                },
                {
                    name: 'Dusun',
                    selector: (item) => item.dusun?.nama || '-',
                    sortable: true,
                    minWidth: '150px',
                },
                {
                    name: 'RT / RW',
                    selector: formatRtRw,
                    sortable: true,
                    minWidth: '135px',
                },
                {
                    name: 'Status Penduduk',
                    selector: (item) => formatLabel(item.status_penduduk),
                    sortable: true,
                    minWidth: '155px',
                    cell: (item) => (
                        <span
                            className={`rounded-full px-3 py-1 text-xs font-semibold ${item.status_penduduk === 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}
                        >
                            {formatLabel(item.status_penduduk)}
                        </span>
                    ),
                },
            ]}
        />
    );
}
