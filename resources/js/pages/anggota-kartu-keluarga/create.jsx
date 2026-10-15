import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { labelKartuKeluarga } from '@/lib/kartu-keluarga';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Search, Trash2, UserPlus, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import DataTable from 'react-data-table-component';

const hubunganOptions = ['Kepala Keluarga', 'Suami', 'Istri', 'Anak', 'Menantu', 'Cucu', 'Orang Tua', 'Mertua', 'Famili Lain', 'Lainnya'].map(
    (value) => ({ value, label: value }),
);

const tableStyles = {
    headCells: {
        style: { backgroundColor: '#f8fafc', color: '#64748b', fontSize: '0.75rem', fontWeight: 700, minHeight: '46px', textTransform: 'uppercase' },
    },
    cells: { style: { color: '#475569', minHeight: '54px' } },
    rows: { style: { '&:hover': { backgroundColor: '#ecfeff' } } },
    pagination: { style: { borderTop: '1px solid #e2e8f0', color: '#64748b' } },
};

const localDate = () => {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 10);
};

export default function AnggotaKartuKeluargaCreate({ kartuKeluargas = [], penduduk = [], selectedKartuKeluargaId = null }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [draftIds, setDraftIds] = useState([]);
    const [tanggalMasukSemua, setTanggalMasukSemua] = useState(localDate());
    const { data, setData, post, processing, errors } = useForm({
        kartu_keluarga_id: selectedKartuKeluargaId ? String(selectedKartuKeluargaId) : '',
        anggotas: [],
    });

    const selectedKartuKeluarga = kartuKeluargas.find((item) => String(item.id) === String(data.kartu_keluarga_id));
    const selectedIds = data.anggotas.map((item) => Number(item.penduduk_id));
    const selectedPenduduk = data.anggotas
        .map((formItem, formIndex) => ({ ...penduduk.find((item) => Number(item.id) === Number(formItem.penduduk_id)), ...formItem, formIndex }))
        .filter((item) => item.id);
    const filteredPenduduk = useMemo(() => {
        const keyword = search.trim().toLowerCase();
        if (!keyword) return penduduk;

        return penduduk.filter((item) =>
            [item.nama_lengkap, item.nik, item.jenis_kelamin, item.alamat].some((value) =>
                String(value || '')
                    .toLowerCase()
                    .includes(keyword),
            ),
        );
    }, [penduduk, search]);

    const openModal = () => {
        setDraftIds(selectedIds);
        setSearch('');
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        setSearch('');
    };

    const applySelection = () => {
        const currentItems = new Map(data.anggotas.map((item) => [Number(item.penduduk_id), item]));
        setData(
            'anggotas',
            draftIds.map((id) => currentItems.get(id) || { penduduk_id: id, hubungan_keluarga: '', tanggal_masuk: tanggalMasukSemua }),
        );
        closeModal();
    };

    const updateAnggota = (index, field, value) => {
        setData(
            'anggotas',
            data.anggotas.map((item, itemIndex) => (itemIndex === index ? { ...item, [field]: value } : item)),
        );
    };

    const removeAnggota = (id) => {
        setData(
            'anggotas',
            data.anggotas.filter((item) => Number(item.penduduk_id) !== id),
        );
    };

    const applyDateToAll = () => {
        setData(
            'anggotas',
            data.anggotas.map((item) => ({ ...item, tanggal_masuk: tanggalMasukSemua })),
        );
    };

    const submit = (event) => {
        event.preventDefault();
        post('/anggota-kartu-keluarga');
    };

    const modalColumns = [
        {
            name: 'Pilih',
            width: '86px',
            center: true,
            ignoreRowClick: true,
            cell: (item) => (
                <input
                    type="checkbox"
                    checked={draftIds.includes(Number(item.id))}
                    onChange={() =>
                        setDraftIds((current) =>
                            current.includes(Number(item.id)) ? current.filter((id) => id !== Number(item.id)) : [...current, Number(item.id)],
                        )
                    }
                    className="size-5 cursor-pointer accent-cyan-600"
                    aria-label={`Pilih ${item.nama_lengkap}`}
                />
            ),
        },
        { name: 'Nama Penduduk', selector: (item) => item.nama_lengkap || '-', sortable: true, grow: 2 },
        { name: 'NIK', selector: (item) => item.nik || '-', sortable: true, minWidth: '170px' },
        { name: 'Jenis Kelamin', selector: (item) => item.jenis_kelamin || '-', sortable: true, minWidth: '135px' },
    ];

    return (
        <VillageDashboardLayout>
            <Head title="Pendaftaran Anggota Kartu Keluarga" />
            <section className="mx-auto max-w-5xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href="/anggota-kartu-keluarga" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600">
                        <ArrowLeft className="size-5" />
                    </Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Kependudukan</p>
                        <h1 className="text-2xl font-bold text-slate-800">Pendaftaran Anggota Kartu Keluarga</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <label className="block">
                        <span className="mb-2 block text-sm font-medium text-slate-700">Kartu Keluarga *</span>
                        <FormSelect
                            value={data.kartu_keluarga_id}
                            options={kartuKeluargas.map((item) => ({
                                value: String(item.id),
                                label: labelKartuKeluarga(item),
                            }))}
                            onChange={(value) => setData({ ...data, kartu_keluarga_id: value, anggotas: [] })}
                            placeholder="Cari dan pilih nomor KK"
                            error={errors.kartu_keluarga_id}
                        />
                        {errors.kartu_keluarga_id && <p className="mt-1 text-xs text-red-600">{errors.kartu_keluarga_id}</p>}
                        {selectedKartuKeluarga?.memiliki_kepala_keluarga && (
                            <p className="mt-2 text-xs text-amber-700">
                                KK ini sudah memiliki kepala keluarga aktif. Pilih hubungan lain untuk anggota baru.
                            </p>
                        )}
                    </label>

                    <div>
                        <div className="mb-2 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <span className="text-sm font-medium text-slate-700">Anggota Keluarga *</span>
                                <p className="text-xs text-slate-500">Daftar hanya menampilkan penduduk yang belum menjadi anggota aktif KK lain.</p>
                            </div>
                            <button
                                type="button"
                                onClick={openModal}
                                disabled={!data.kartu_keluarga_id}
                                className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <UserPlus className="size-4" /> Pilih Penduduk
                            </button>
                        </div>
                        {errors.anggotas && <p className="mb-2 text-xs text-red-600">{errors.anggotas}</p>}

                        {selectedPenduduk.length > 0 ? (
                            <div className="space-y-4">
                                <div className="flex flex-wrap items-end gap-3 rounded-lg border border-cyan-200 bg-cyan-50 p-4">
                                    <label className="min-w-56 flex-1">
                                        <span className="mb-1 block text-xs font-medium text-slate-600">Tanggal Masuk Awal</span>
                                        <input
                                            type="date"
                                            value={tanggalMasukSemua}
                                            onChange={(event) => setTanggalMasukSemua(event.target.value)}
                                            className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none focus:border-cyan-500"
                                        />
                                    </label>
                                    <button
                                        type="button"
                                        onClick={applyDateToAll}
                                        className="h-10 rounded-md border border-cyan-600 bg-white px-4 text-xs font-semibold text-cyan-700 hover:bg-cyan-100"
                                    >
                                        Terapkan ke Semua
                                    </button>
                                </div>
                                <div className="flex items-center justify-between">
                                    <h2 className="font-semibold text-slate-800">Calon Anggota</h2>
                                    <p className="text-sm text-slate-600">
                                        <span className="font-semibold text-cyan-700">{selectedPenduduk.length}</span> penduduk
                                    </p>
                                </div>
                                {selectedPenduduk.map((item) => (
                                    <div key={item.id} className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                        <div className="mb-4 flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-semibold text-slate-800">{item.nama_lengkap}</p>
                                                <p className="text-sm text-slate-500">
                                                    NIK {item.nik} · {item.jenis_kelamin}
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => removeAnggota(Number(item.id))}
                                                className="inline-flex items-center gap-1 rounded-md bg-red-500 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-600"
                                            >
                                                <Trash2 className="size-3.5" /> Hapus
                                            </button>
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <label>
                                                <span className="mb-1 block text-xs font-medium text-slate-600">Hubungan Keluarga *</span>
                                                <FormSelect
                                                    value={item.hubungan_keluarga}
                                                    options={hubunganOptions}
                                                    onChange={(value) => updateAnggota(item.formIndex, 'hubungan_keluarga', value)}
                                                    placeholder="Pilih hubungan keluarga"
                                                    error={errors[`anggotas.${item.formIndex}.hubungan_keluarga`]}
                                                />
                                                {errors[`anggotas.${item.formIndex}.hubungan_keluarga`] && (
                                                    <p className="mt-1 text-xs text-red-600">
                                                        {errors[`anggotas.${item.formIndex}.hubungan_keluarga`]}
                                                    </p>
                                                )}
                                            </label>
                                            <label>
                                                <span className="mb-1 block text-xs font-medium text-slate-600">Tanggal Masuk *</span>
                                                <input
                                                    required
                                                    type="date"
                                                    value={item.tanggal_masuk}
                                                    onChange={(event) => updateAnggota(item.formIndex, 'tanggal_masuk', event.target.value)}
                                                    className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-cyan-500"
                                                />
                                                {errors[`anggotas.${item.formIndex}.tanggal_masuk`] && (
                                                    <p className="mt-1 text-xs text-red-600">{errors[`anggotas.${item.formIndex}.tanggal_masuk`]}</p>
                                                )}
                                            </label>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                                {data.kartu_keluarga_id ? 'Belum ada penduduk yang dipilih.' : 'Pilih kartu keluarga terlebih dahulu.'}
                            </div>
                        )}
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link
                            href="/anggota-kartu-keluarga"
                            className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Batal
                        </Link>
                        <button
                            disabled={processing || data.anggotas.length === 0}
                            className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-50"
                        >
                            <UserPlus className="size-4" /> {processing ? 'Mendaftarkan...' : 'Daftarkan Anggota'}
                        </button>
                    </div>
                </form>
            </section>

            {modalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true">
                    <div className="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
                        <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <div>
                                <h2 className="text-lg font-bold text-slate-800">Pilih Penduduk</h2>
                                <p className="text-sm text-slate-500">
                                    Centang penduduk yang akan dimasukkan ke KK {selectedKartuKeluarga?.nomor_kk}.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={closeModal}
                                className="rounded-md p-2 text-slate-500 hover:bg-slate-100"
                                aria-label="Tutup modal"
                            >
                                <X className="size-5" />
                            </button>
                        </div>
                        <div className="min-h-0 flex-1 overflow-auto p-5">
                            <label className="relative mb-4 block">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    type="search"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Cari nama, NIK, jenis kelamin, atau alamat..."
                                    autoFocus
                                    className="h-11 w-full rounded-md border border-slate-200 pr-3 pl-10 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100"
                                />
                            </label>
                            <div className="overflow-hidden rounded-lg border border-slate-200">
                                <DataTable
                                    columns={modalColumns}
                                    data={filteredPenduduk}
                                    keyField="id"
                                    customStyles={tableStyles}
                                    pagination
                                    paginationPerPage={10}
                                    paginationRowsPerPageOptions={[10, 25, 50]}
                                    noDataComponent={<p className="py-10 text-sm text-slate-500">Tidak ada penduduk yang dapat didaftarkan.</p>}
                                />
                            </div>
                        </div>
                        <div className="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-slate-600">
                                <span className="font-semibold text-cyan-700">{draftIds.length}</span> penduduk dicentang
                            </p>
                            <div className="flex justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={closeModal}
                                    className="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    onClick={applySelection}
                                    className="rounded-md bg-cyan-600 px-5 py-2 text-sm font-semibold text-white hover:bg-cyan-700"
                                >
                                    Pilih ({draftIds.length})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </VillageDashboardLayout>
    );
}
