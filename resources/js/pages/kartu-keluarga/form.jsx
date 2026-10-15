import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

const defaultForm = {
    desa_id: '',
    dusun_id: '',
    rw_id: '',
    rt_id: '',
    penduduk_id: '',
    nomor_kk: '',
    alamat: '',
    kode_pos: '',
    tanggal_diterbitkan: '',
    status: 'aktif',
    anggotas: [],
};

const hubunganOptions = ['Suami', 'Istri', 'Anak', 'Menantu', 'Cucu', 'Orang Tua', 'Mertua', 'Famili Lain', 'Lainnya'].map((value) => ({ value, label: value }));

export default function KartuKeluargaForm({ kartuKeluarga, penduduk = [], desas = [], dusuns = [], rws = [], rts = [] }) {
    const editing = Boolean(kartuKeluarga);
    const { data, setData, post, put, processing, errors } = useForm({
        ...defaultForm,
        ...(kartuKeluarga || {}),
        desa_id: kartuKeluarga?.desa_id || desas[0]?.id || '',
        penduduk_id: kartuKeluarga?.kepala_keluarga?.penduduk_id || kartuKeluarga?.kepala_keluarga?.penduduk?.id || '',
    });

    const [selectedDesaId, setSelectedDesaId] = useState(kartuKeluarga?.desa_id || desas[0]?.id || '');
    const [selectedDusunId, setSelectedDusunId] = useState(kartuKeluarga?.dusun_id || '');
    const [selectedRwId, setSelectedRwId] = useState(kartuKeluarga?.rw_id || '');
    const [selectedRtId, setSelectedRtId] = useState(kartuKeluarga?.rt_id || '');

    const filteredDusuns = useMemo(
        () => dusuns.filter((item) => !selectedDesaId || String(item.desa_id) === String(selectedDesaId)),
        [dusuns, selectedDesaId]
    );

    const filteredRws = useMemo(
        () => rws.filter((item) => !selectedDusunId || String(item.dusun_id) === String(selectedDusunId)),
        [rws, selectedDusunId]
    );

    const filteredRts = useMemo(
        () => rts.filter((item) => !selectedRwId || String(item.rw_id) === String(selectedRwId)),
        [rts, selectedRwId]
    );

    const filteredPenduduk = useMemo(
        () => penduduk.filter((item) => {
            const matchesDusun = !selectedDusunId || String(item.dusun_id) === String(selectedDusunId);
            const matchesRw = !selectedRwId || String(item.rw_id) === String(selectedRwId);
            const matchesRt = !selectedRtId || String(item.rt_id) === String(selectedRtId);
            return matchesDusun && matchesRw && matchesRt;
        }),
        [penduduk, selectedDusunId, selectedRwId, selectedRtId]
    );

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

    const handleDesaChange = (value) => {
        setSelectedDesaId(value);
        setData('desa_id', value);
        setData('dusun_id', '');
        setSelectedDusunId('');
        setData('rw_id', '');
        setSelectedRwId('');
        setData('rt_id', '');
        setData('penduduk_id', '');
    };

    const handleDusunChange = (value) => {
        setSelectedDusunId(value);
        setData('dusun_id', value);
        setData('penduduk_id', '');

        if (value === '') {
            setData('rw_id', '');
            setSelectedRwId('');
            setData('rt_id', '');
            setSelectedRtId('');
            return;
        }

        const nextRws = rws.filter((item) => String(item.dusun_id) === String(value));
        if (!nextRws.some((item) => String(item.id) === String(data.rw_id))) {
            setData('rw_id', '');
            setSelectedRwId('');
        }
        setData('rt_id', '');
        setSelectedRtId('');
    };

    const handleRwChange = (value) => {
        setSelectedRwId(value);
        setData('rw_id', value);
        setData('penduduk_id', '');
        if (value === '') {
            setData('rt_id', '');
            setSelectedRtId('');
            return;
        }

        const nextRts = rts.filter((item) => String(item.rw_id) === String(value));
        if (!nextRts.some((item) => String(item.id) === String(data.rt_id))) {
            setData('rt_id', '');
            setSelectedRtId('');
        }
    };

    const submit = (event) => {
        event.preventDefault();
        editing ? put(`/kartu-keluarga/${kartuKeluarga.id}`) : post('/kartu-keluarga');
    };

    const addAnggota = () => setData('anggotas', [...data.anggotas, { penduduk_id: '', hubungan_keluarga: '', tanggal_masuk: new Date().toISOString().slice(0, 10) }]);
    const updateAnggota = (index, field, value) => setData('anggotas', data.anggotas.map((item, itemIndex) => itemIndex === index ? { ...item, [field]: value } : item));
    const removeAnggota = (index) => setData('anggotas', data.anggotas.filter((_, itemIndex) => itemIndex !== index));

    const fieldError = (field) => errors[field] && <p className="mt-1 text-xs text-red-600">{errors[field]}</p>;

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit Kartu Keluarga' : 'Tambah Kartu Keluarga'} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={editing ? `/kartu-keluarga/${kartuKeluarga.id}` : '/kartu-keluarga'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Kependudukan</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit Kartu Keluarga' : 'Tambah Kartu Keluarga'}</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="grid gap-5 md:grid-cols-2">
                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Kode Pos</span>
                            <input value={data.kode_pos || ''} onChange={(event) => setData('kode_pos', event.target.value)} className={inputClass('kode_pos')} />
                            {fieldError('kode_pos')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Dusun')}</span>
                            <FormSelect value={data.dusun_id} options={filteredDusuns.map((item) => ({ value: String(item.id), label: item.nama }))} onChange={handleDusunChange} placeholder="Pilih Dusun" error={errors.dusun_id} className="w-full" />
                            {fieldError('dusun_id')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('RW')}</span>
                            <FormSelect value={data.rw_id} options={filteredRws.map((item) => ({ value: String(item.id), label: `RW ${item.nomor}` }))} onChange={handleRwChange} placeholder="Pilih RW" error={errors.rw_id} className="w-full" />
                            {fieldError('rw_id')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('RT')}</span>
                            <FormSelect
                                value={data.rt_id}
                                options={filteredRts.map((item) => ({ value: String(item.id), label: `RT ${item.nomor}` }))}
                                onChange={(value) => {
                                    setSelectedRtId(value);
                                    setData('rt_id', value);
                                    setData('penduduk_id', '');
                                }}
                                placeholder="Pilih RT"
                                error={errors.rt_id}
                                className="w-full"
                            />
                            {fieldError('rt_id')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Tanggal Diterbitkan')}</span>
                            <input type="date" value={data.tanggal_diterbitkan || ''} onChange={(event) => setData('tanggal_diterbitkan', event.target.value)} className={inputClass('tanggal_diterbitkan')} />
                            {fieldError('tanggal_diterbitkan')}
                        </label>

                        <label className="block md:col-span-2">
                            <span>{requiredLabel('Kepala Keluarga')}</span>
                            <FormSelect
                                value={data.penduduk_id || ''}
                                options={filteredPenduduk.map((item) => ({ value: String(item.id), label: `${item.nama_lengkap} (${item.nik})` }))}
                                onChange={(value) => setData('penduduk_id', value)}
                                placeholder={selectedRtId ? 'Pilih Kepala Keluarga' : selectedRwId ? 'Pilih RT terlebih dahulu' : selectedDusunId ? 'Pilih RW/RT terlebih dahulu' : 'Pilih Dusun terlebih dahulu'}
                                error={errors.penduduk_id}
                                className="w-full"
                            />
                            {fieldError('penduduk_id')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Nomor KK')}</span>
                            <input value={data.nomor_kk} onChange={(event) => setData('nomor_kk', event.target.value)} className={inputClass('nomor_kk')} />
                            {fieldError('nomor_kk')}
                        </label>

                        <label className="block md:col-span-2">
                            <span>{requiredLabel('Alamat')}</span>
                            <textarea value={data.alamat} onChange={(event) => setData('alamat', event.target.value)} rows={3} className={inputClass('alamat', 'w-full rounded-md border px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100')}></textarea>
                            {fieldError('alamat')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Status')}</span>
                            <FormSelect value={data.status} options={[{ value: 'aktif', label: 'Aktif' }, { value: 'nonaktif', label: 'Nonaktif' }]} onChange={(value) => setData('status', value)} placeholder="Pilih Status" error={errors.status} className="w-full" />
                            {fieldError('status')}
                        </label>
                    </div>

                    {!editing && <section className="space-y-4 border-t border-slate-100 pt-5">
                        <div className="flex items-center justify-between gap-3"><div><h2 className="font-semibold text-slate-800">Anggota Keluarga</h2><p className="text-sm text-slate-500">Tambahkan anggota lain bersama pembuatan KK. Kepala keluarga otomatis didaftarkan dari pilihan di atas.</p></div><button type="button" onClick={addAnggota} className="inline-flex items-center gap-2 rounded-md border border-cyan-200 px-3 py-2 text-sm font-semibold text-cyan-700"><Plus className="size-4" />Tambah Anggota</button></div>
                        {data.anggotas.map((anggota, index) => <div key={index} className="grid gap-4 rounded-lg border border-slate-200 p-4 md:grid-cols-[1fr_1fr_180px_auto]">
                            <FormSelect value={anggota.penduduk_id} options={filteredPenduduk.filter((item) => String(item.id) !== String(data.penduduk_id) && !data.anggotas.some((row, rowIndex) => rowIndex !== index && String(row.penduduk_id) === String(item.id))).map((item) => ({ value: String(item.id), label: `${item.nama_lengkap} (${item.nik})` }))} onChange={(value) => updateAnggota(index, 'penduduk_id', value)} placeholder="Pilih penduduk" error={errors[`anggotas.${index}.penduduk_id`]} />
                            <FormSelect value={anggota.hubungan_keluarga} options={hubunganOptions} onChange={(value) => updateAnggota(index, 'hubungan_keluarga', value)} placeholder="Hubungan keluarga" error={errors[`anggotas.${index}.hubungan_keluarga`]} />
                            <input type="date" value={anggota.tanggal_masuk} onChange={(event) => updateAnggota(index, 'tanggal_masuk', event.target.value)} className={inputClass(`anggotas.${index}.tanggal_masuk`)} />
                            <button type="button" onClick={() => removeAnggota(index)} className="rounded-md p-2 text-red-600 hover:bg-red-50" aria-label={`Hapus anggota ${index + 1}`}><Trash2 className="size-4" /></button>
                        </div>)}
                    </section>}

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href={editing ? `/kartu-keluarga/${kartuKeluarga.id}` : '/kartu-keluarga'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan'}</button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
