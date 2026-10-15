import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

const defaultForm = {
    penduduk_id: '',
    tanggal_meninggal: '',
    waktu_meninggal: '',
    tempat_meninggal: '',
    penyebab_meninggal: '',
    nomor_surat_kematian: '',
    tanggal_dilaporkan: '',
    catatan: '',
};

export default function KematianForm({ kematian, penduduk = [] }) {
    const editing = Boolean(kematian);
    const { data, setData, post, put, processing, errors } = useForm({
        ...defaultForm,
        ...(kematian || {}),
    });

    const selectedPenduduk = penduduk.find((item) => String(item.id) === String(data.penduduk_id));
    const selectedKk = Array.isArray(selectedPenduduk?.anggota_kartu_keluarga) && selectedPenduduk.anggota_kartu_keluarga.length > 0
        ? selectedPenduduk.anggota_kartu_keluarga[0]
        : null;

    const inputClass = (field, baseClass = 'h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100') => {
        const errorClass = errors[field] ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white';
        return `${baseClass} ${errorClass}`;
    };

    const requiredLabel = (label) => (
        <span className="mb-2 flex items-center gap-1 text-sm font-medium text-slate-700">
            <span>{label}</span>
            <span className="text-red-600">*</span>
        </span>
    );

    const fieldError = (field) => errors[field] && <p className="mt-1 text-xs text-red-600">{errors[field]}</p>;

    const submit = (event) => {
        event.preventDefault();
        editing ? put(`/kematian/${kematian.id}`) : post('/kematian');
    };

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit Kematian' : 'Tambah Kematian'} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={editing ? `/kematian/${kematian.id}` : '/kematian'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Peristiwa Kependudukan</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit Kematian' : 'Tambah Kematian'}</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="grid gap-5 md:grid-cols-2">
                        <label className="block md:col-span-2">
                            <span>{requiredLabel('Penduduk')}</span>
                            <FormSelect value={data.penduduk_id || ''} options={penduduk.map((item) => ({ value: String(item.id), label: `${item.nik || '-'} - ${item.nama_lengkap} - ${item.dusun?.nama || '-'}` }))} onChange={(value) => setData('penduduk_id', value)} placeholder="Pilih penduduk" error={errors.penduduk_id} className="w-full" />
                            {fieldError('penduduk_id')}
                        </label>

                        {selectedPenduduk && (
                            <div className="md:col-span-2 rounded-md border border-slate-200 bg-slate-50 p-4">
                                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Informasi Penduduk</h3>
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div><p className="text-xs uppercase text-slate-400">NIK</p><p className="mt-1 font-medium text-slate-800">{selectedPenduduk.nik || '-'}</p></div>
                                    <div><p className="text-xs uppercase text-slate-400">Nama Lengkap</p><p className="mt-1 font-medium text-slate-800">{selectedPenduduk.nama_lengkap || '-'}</p></div>
                                    <div><p className="text-xs uppercase text-slate-400">Nomor KK</p><p className="mt-1 font-medium text-slate-800">{selectedKk?.kartu_keluarga?.nomor_kk || '-'}</p></div>
                                    <div><p className="text-xs uppercase text-slate-400">Hubungan Dalam Keluarga</p><p className="mt-1 font-medium text-slate-800">{selectedKk?.hubungan_keluarga || '-'}</p></div>
                                    <div><p className="text-xs uppercase text-slate-400">Alamat</p><p className="mt-1 font-medium text-slate-800">{selectedPenduduk.alamat || '-'}</p></div>
                                    <div><p className="text-xs uppercase text-slate-400">Dusun</p><p className="mt-1 font-medium text-slate-800">{selectedPenduduk.dusun?.nama || '-'}</p></div>
                                    <div><p className="text-xs uppercase text-slate-400">RW / RT</p><p className="mt-1 font-medium text-slate-800">{selectedPenduduk.rw?.nomor || '-'} / {selectedPenduduk.rt?.nomor || '-'}</p></div>
                                    <div><p className="text-xs uppercase text-slate-400">Tanggal Lahir</p><p className="mt-1 font-medium text-slate-800">{selectedPenduduk.tanggal_lahir || '-'}</p></div>
                                    <div><p className="text-xs uppercase text-slate-400">Umur</p><p className="mt-1 font-medium text-slate-800">{selectedPenduduk.tanggal_lahir ? `${Math.floor((new Date() - new Date(selectedPenduduk.tanggal_lahir)) / (1000 * 60 * 60 * 24 * 365.25))} tahun` : '-'}</p></div>
                                    <div><p className="text-xs uppercase text-slate-400">Status Penduduk</p><p className="mt-1 font-medium text-slate-800">{selectedPenduduk.status_penduduk || '-'}</p></div>
                                    {selectedKk?.hubungan_keluarga === 'Kepala Keluarga' && (
                                        <div className="md:col-span-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                                            Kepala Keluarga
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        <label className="block">
                            <span>{requiredLabel('Tanggal Meninggal')}</span>
                            <input type="date" value={data.tanggal_meninggal || ''} onChange={(event) => setData('tanggal_meninggal', event.target.value)} className={inputClass('tanggal_meninggal')} />
                            {fieldError('tanggal_meninggal')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Waktu Meninggal</span>
                            <input type="time" value={data.waktu_meninggal || ''} onChange={(event) => setData('waktu_meninggal', event.target.value)} className={inputClass('waktu_meninggal')} />
                            {fieldError('waktu_meninggal')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Tempat Meninggal</span>
                            <input value={data.tempat_meninggal || ''} onChange={(event) => setData('tempat_meninggal', event.target.value)} className={inputClass('tempat_meninggal')} />
                            {fieldError('tempat_meninggal')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Penyebab Meninggal</span>
                            <input value={data.penyebab_meninggal || ''} onChange={(event) => setData('penyebab_meninggal', event.target.value)} className={inputClass('penyebab_meninggal')} />
                            {fieldError('penyebab_meninggal')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Nomor Surat Kematian</span>
                            <input value={data.nomor_surat_kematian || ''} onChange={(event) => setData('nomor_surat_kematian', event.target.value)} className={inputClass('nomor_surat_kematian')} />
                            {fieldError('nomor_surat_kematian')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Tanggal Dilaporkan</span>
                            <input type="date" value={data.tanggal_dilaporkan || ''} onChange={(event) => setData('tanggal_dilaporkan', event.target.value)} className={inputClass('tanggal_dilaporkan')} />
                            {fieldError('tanggal_dilaporkan')}
                        </label>

                        <label className="block md:col-span-2">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Catatan</span>
                            <textarea value={data.catatan || ''} onChange={(event) => setData('catatan', event.target.value)} rows={3} className={`${inputClass('catatan', 'w-full rounded-md border px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100')} ${errors.catatan ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 bg-white'}`} />
                            {fieldError('catatan')}
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href={editing ? `/kematian/${kematian.id}` : '/kematian'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan'}</button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
