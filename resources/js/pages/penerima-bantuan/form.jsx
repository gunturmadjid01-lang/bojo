import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Search, Trash2, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import DataTable from 'react-data-table-component';
import Select from 'react-select';

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

const newRecipient = (id, defaults) => ({
    penerima_id: Number(id),
    tanggal_terdaftar: defaults.tanggal_terdaftar,
    catatan: defaults.catatan,
});

export default function Form({ programs = [], penduduks = [], kartuKeluargas = [] }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [draftIds, setDraftIds] = useState([]);
    const [defaults, setDefaults] = useState({ tanggal_terdaftar: localDate(), catatan: '' });
    const { data, setData, post, processing, errors } = useForm({
        program_bantuan_id: '',
        jenis_penerima: 'penduduk',
        penerimas: [],
    });

    const isPenduduk = data.jenis_penerima === 'penduduk';
    const recipients = useMemo(
        () =>
            (isPenduduk ? penduduks : kartuKeluargas).map((item) => ({
                ...item,
                id: Number(item.id),
                nama: isPenduduk ? item.nama_lengkap : `KK ${item.nomor_kk}`,
                identitas: isPenduduk ? item.nik || '-' : item.nomor_kk || '-',
            })),
        [isPenduduk, penduduks, kartuKeluargas],
    );
    const selectedIds = data.penerimas.map((item) => Number(item.penerima_id));
    const selectedRecipients = data.penerimas
        .map((formItem, index) => ({ ...recipients.find((item) => item.id === Number(formItem.penerima_id)), ...formItem, formIndex: index }))
        .filter((item) => item.id);
    const filteredRecipients = useMemo(() => {
        const keyword = search.trim().toLowerCase();
        if (!keyword) return recipients;

        return recipients.filter((item) =>
            [item.nama, item.identitas, item.alamat].some((value) =>
                String(value || '')
                    .toLowerCase()
                    .includes(keyword),
            ),
        );
    }, [recipients, search]);

    const openRecipientModal = () => {
        setDraftIds(selectedIds);
        setSearch('');
        setModalOpen(true);
    };

    const closeRecipientModal = () => {
        setModalOpen(false);
        setSearch('');
    };

    const toggleDraft = (id) => {
        setDraftIds((current) => (current.includes(id) ? current.filter((itemId) => itemId !== id) : [...current, id]));
    };

    const applyRecipients = () => {
        const currentItems = new Map(data.penerimas.map((item) => [Number(item.penerima_id), item]));
        setData(
            'penerimas',
            draftIds.map((id) => currentItems.get(id) || newRecipient(id, defaults)),
        );
        closeRecipientModal();
    };

    const updateRecipient = (index, field, value) => {
        setData(
            'penerimas',
            data.penerimas.map((item, itemIndex) => (itemIndex === index ? { ...item, [field]: value } : item)),
        );
    };

    const removeRecipient = (id) => {
        setData(
            'penerimas',
            data.penerimas.filter((item) => Number(item.penerima_id) !== id),
        );
    };

    const applyDefaultsToAll = () => {
        setData(
            'penerimas',
            data.penerimas.map((item) => ({ ...item, ...defaults })),
        );
    };

    const changeRecipientType = (value) => {
        setData({ ...data, jenis_penerima: value, penerimas: [] });
        setDraftIds([]);
        setSearch('');
    };

    const submit = (event) => {
        event.preventDefault();
        post('/penerima-bantuan');
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
                    checked={draftIds.includes(item.id)}
                    onChange={() => toggleDraft(item.id)}
                    className="size-5 cursor-pointer accent-cyan-600"
                    aria-label={`Pilih ${item.nama}`}
                />
            ),
        },
        { name: isPenduduk ? 'Nama Penduduk' : 'Nomor KK', selector: (item) => item.nama, sortable: true, grow: 2 },
        { name: isPenduduk ? 'NIK' : 'Alamat', selector: (item) => (isPenduduk ? item.identitas : item.alamat || '-'), sortable: true, grow: 2 },
    ];

    return (
        <VillageDashboardLayout>
            <Head title="Tambah Penerima Bantuan" />
            <section className="mx-auto max-w-5xl">
                <h1 className="mb-5 text-2xl font-bold text-slate-800">Tambah Penerima Bantuan</h1>
                <form onSubmit={submit} className="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="grid gap-5 md:grid-cols-2">
                        <label className="block">
                            <span className="mb-2 block text-sm font-medium">Program Bantuan *</span>
                            <Select
                                options={programs.map((item) => ({ value: item.id, label: item.nama }))}
                                value={
                                    programs
                                        .map((item) => ({ value: item.id, label: item.nama }))
                                        .find((item) => String(item.value) === String(data.program_bantuan_id)) || null
                                }
                                onChange={(item) => setData('program_bantuan_id', item?.value || '')}
                                placeholder="Pilih program bantuan"
                                noOptionsMessage={() => 'Program tidak ditemukan'}
                            />
                            {errors.program_bantuan_id && <p className="mt-1 text-xs text-red-600">{errors.program_bantuan_id}</p>}
                        </label>
                        <label className="block">
                            <span className="mb-2 block text-sm font-medium">Jenis Penerima *</span>
                            <Select
                                options={[
                                    { value: 'penduduk', label: 'Penduduk' },
                                    { value: 'kartu_keluarga', label: 'Kartu Keluarga' },
                                ]}
                                value={{ value: data.jenis_penerima, label: isPenduduk ? 'Penduduk' : 'Kartu Keluarga' }}
                                onChange={(item) => changeRecipientType(item.value)}
                                isSearchable={false}
                            />
                        </label>
                    </div>

                    <div>
                        <div className="mb-2 flex items-center justify-between gap-3">
                            <span className="text-sm font-medium">Penerima *</span>
                            <button
                                type="button"
                                onClick={openRecipientModal}
                                className="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700"
                            >
                                Pilih {isPenduduk ? 'Penduduk' : 'Kartu Keluarga'}
                            </button>
                        </div>
                        {errors.penerimas && <p className="mb-2 text-xs text-red-600">{errors.penerimas}</p>}

                        {selectedRecipients.length > 0 ? (
                            <div className="space-y-4">
                                <div className="rounded-lg border border-cyan-200 bg-cyan-50 p-4">
                                    <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <h2 className="text-sm font-semibold text-slate-800">Nilai awal penerima</h2>
                                            <p className="text-xs text-slate-500">
                                                Dipakai untuk penerima baru dan dapat diterapkan ke semua data terpilih.
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={applyDefaultsToAll}
                                            className="rounded-md border border-cyan-600 bg-white px-3 py-2 text-xs font-semibold text-cyan-700 hover:bg-cyan-100"
                                        >
                                            Terapkan ke Semua
                                        </button>
                                    </div>
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <label>
                                            <span className="mb-1 block text-xs font-medium text-slate-600">Tanggal Terdaftar</span>
                                            <input
                                                type="date"
                                                value={defaults.tanggal_terdaftar}
                                                onChange={(event) => setDefaults({ ...defaults, tanggal_terdaftar: event.target.value })}
                                                className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none focus:border-cyan-500"
                                            />
                                        </label>
                                        <label>
                                            <span className="mb-1 block text-xs font-medium text-slate-600">Catatan Awal</span>
                                            <input
                                                value={defaults.catatan}
                                                onChange={(event) => setDefaults({ ...defaults, catatan: event.target.value })}
                                                placeholder="Opsional"
                                                className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none focus:border-cyan-500"
                                            />
                                        </label>
                                    </div>
                                </div>

                                <div className="flex items-center justify-between">
                                    <h2 className="font-semibold text-slate-800">Daftar Penerima</h2>
                                    <p className="text-sm text-slate-600">
                                        <span className="font-semibold text-cyan-700">{selectedRecipients.length}</span> data terpilih
                                    </p>
                                </div>

                                {selectedRecipients.map((item) => (
                                    <div key={item.id} className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                        <div className="mb-4 flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-semibold text-slate-800">{item.nama}</p>
                                                <p className="text-sm text-slate-500">{isPenduduk ? `NIK: ${item.identitas}` : item.alamat || '-'}</p>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => removeRecipient(item.id)}
                                                className="inline-flex items-center gap-1 rounded-md bg-red-500 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-600"
                                            >
                                                <Trash2 className="size-3.5" /> Hapus
                                            </button>
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <label>
                                                <span className="mb-1 block text-xs font-medium text-slate-600">Tanggal Terdaftar *</span>
                                                <input
                                                    required
                                                    type="date"
                                                    value={item.tanggal_terdaftar}
                                                    onChange={(event) => updateRecipient(item.formIndex, 'tanggal_terdaftar', event.target.value)}
                                                    className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none focus:border-cyan-500"
                                                />
                                                {errors[`penerimas.${item.formIndex}.tanggal_terdaftar`] && (
                                                    <p className="mt-1 text-xs text-red-600">
                                                        {errors[`penerimas.${item.formIndex}.tanggal_terdaftar`]}
                                                    </p>
                                                )}
                                            </label>
                                            <label>
                                                <span className="mb-1 block text-xs font-medium text-slate-600">Catatan Pendaftaran</span>
                                                <input
                                                    value={item.catatan || ''}
                                                    onChange={(event) => updateRecipient(item.formIndex, 'catatan', event.target.value)}
                                                    placeholder="Opsional"
                                                    className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none focus:border-cyan-500"
                                                />
                                            </label>
                                        </div>
                                        <div className="mt-3 rounded-md bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                                            Status awal: Menunggu Verifikasi
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                                Belum ada {isPenduduk ? 'penduduk' : 'kartu keluarga'} yang dipilih.
                            </div>
                        )}
                    </div>

                    <div className="flex justify-end gap-3">
                        <Link
                            href="/penerima-bantuan"
                            className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Batal
                        </Link>
                        <button
                            disabled={processing}
                            className="rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"
                        >
                            {processing ? 'Menyimpan...' : 'Daftarkan Semua Penerima'}
                        </button>
                    </div>
                </form>
            </section>

            {modalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true">
                    <div className="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
                        <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <div>
                                <h2 className="text-lg font-bold text-slate-800">Pilih {isPenduduk ? 'Penduduk' : 'Kartu Keluarga'}</h2>
                                <p className="mt-0.5 text-sm text-slate-500">Centang satu atau beberapa data penerima bantuan.</p>
                            </div>
                            <button
                                type="button"
                                onClick={closeRecipientModal}
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
                                    placeholder={isPenduduk ? 'Cari nama atau NIK...' : 'Cari nomor KK atau alamat...'}
                                    autoFocus
                                    className="h-11 w-full rounded-md border border-slate-200 pr-3 pl-10 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100"
                                />
                            </label>
                            <div className="overflow-hidden rounded-lg border border-slate-200">
                                <DataTable
                                    columns={modalColumns}
                                    data={filteredRecipients}
                                    keyField="id"
                                    customStyles={tableStyles}
                                    pagination
                                    paginationPerPage={10}
                                    paginationRowsPerPageOptions={[10, 25, 50]}
                                    noDataComponent={<p className="py-10 text-sm text-slate-500">Data tidak ditemukan.</p>}
                                />
                            </div>
                        </div>
                        <div className="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-slate-600">
                                <span className="font-semibold text-cyan-700">{draftIds.length}</span> data dicentang
                            </p>
                            <div className="flex justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={closeRecipientModal}
                                    className="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    onClick={applyRecipients}
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
