import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

const defaultForm = {
    nik: '',
    nama_lengkap: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    jenis_kelamin: 'Laki-laki',
    agama: '',
    golongan_darah: '',
    status_perkawinan: '',
    pendidikan_id: '',
    pekerjaan_id: '',
    kewarganegaraan: 'WNI',
    nama_ayah: '',
    nama_ibu: '',
    telepon: '',
    alamat: '',
    dusun_id: '',
    rw_id: '',
    rt_id: '',
    status_penduduk: 'aktif',
    masih_hidup: true,
    foto: '',
};

const agamaOptions = [
    { value: 'Islam', label: 'Islam' },
    { value: 'Kristen', label: 'Kristen' },
    { value: 'Katolik', label: 'Katolik' },
    { value: 'Hindu', label: 'Hindu' },
    { value: 'Buddha', label: 'Buddha' },
    { value: 'Konghucu', label: 'Konghucu' },
    { value: 'Kepercayaan', label: 'Kepercayaan' },
];

export default function PendudukForm({ penduduk, dusuns = [], rws = [], rts = [], pendidikans = [], pekerjaans = [], kartuKeluargas = [] }) {
    const editing = Boolean(penduduk);
    const { data, setData, post, put, processing, errors } = useForm({
        ...defaultForm,
        ...(penduduk || {}),
        kartu_keluarga_id: '',
        nomor_kk: '',
        tanggal_diterbitkan: '',
        hubungan_keluarga_utama: 'Kepala Keluarga',
        penduduks: [],
    });

    const addPenduduk = () => setData('penduduks', [...data.penduduks, { nik: '', nama_lengkap: '', tempat_lahir: '', tanggal_lahir: '', jenis_kelamin: 'Laki-laki', hubungan_keluarga: 'Anak' }]);
    const updatePenduduk = (index, field, value) => setData('penduduks', data.penduduks.map((item, itemIndex) => itemIndex === index ? { ...item, [field]: value } : item));
    const removePenduduk = (index) => setData('penduduks', data.penduduks.filter((_, itemIndex) => itemIndex !== index));

    const [selectedDusunId, setSelectedDusunId] = useState(penduduk?.dusun_id || '');
    const [selectedRwId, setSelectedRwId] = useState(penduduk?.rw_id || '');

    const filteredRws = useMemo(
        () => rws.filter((item) => !selectedDusunId || String(item.dusun_id) === String(selectedDusunId)),
        [rws, selectedDusunId]
    );

    const filteredRts = useMemo(
        () => rts.filter((item) => !selectedRwId || String(item.rw_id) === String(selectedRwId)),
        [rts, selectedRwId]
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

    const handleDusunChange = (value) => {
        setSelectedDusunId(value);
        setData('dusun_id', value);
        if (value === '') {
            setData('rw_id', '');
            setSelectedRwId('');
            return;
        }

        const nextRws = rws.filter((item) => String(item.dusun_id) === String(value));
        if (!nextRws.some((item) => String(item.id) === String(data.rw_id))) {
            setData('rw_id', '');
            setSelectedRwId('');
        }
    };

    const handleRwChange = (value) => {
        setSelectedRwId(value);
        setData('rw_id', value);
        if (value === '') {
            setData('rt_id', '');
            return;
        }

        const nextRts = rts.filter((item) => String(item.rw_id) === String(value));
        if (!nextRts.some((item) => String(item.id) === String(data.rt_id))) {
            setData('rt_id', '');
        }
    };

    const submit = (event) => {
        event.preventDefault();
        editing ? put(`/penduduk/${penduduk.id}`) : post('/penduduk');
    };

    const fieldError = (field) => errors[field] && <p className="mt-1 text-xs text-red-600">{errors[field]}</p>;

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit Penduduk' : 'Tambah Penduduk'} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={editing ? `/penduduk/${penduduk.id}` : '/penduduk'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Kependudukan</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit Penduduk' : 'Tambah Penduduk'}</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="grid gap-5 md:grid-cols-2">
                        <label className="block">
                            <span>{requiredLabel('NIK')}</span>
                            <input value={data.nik} onChange={(event) => setData('nik', event.target.value)} className={inputClass('nik')} />
                            {fieldError('nik')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Nama Lengkap')}</span>
                            <input value={data.nama_lengkap} onChange={(event) => setData('nama_lengkap', event.target.value)} className={inputClass('nama_lengkap')} />
                            {fieldError('nama_lengkap')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Tempat Lahir</span>
                            <input value={data.tempat_lahir || ''} onChange={(event) => setData('tempat_lahir', event.target.value)} className={inputClass('tempat_lahir')} />
                            {fieldError('tempat_lahir')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Tanggal Lahir</span>
                            <input type="date" value={data.tanggal_lahir || ''} onChange={(event) => setData('tanggal_lahir', event.target.value)} className={inputClass('tanggal_lahir')} />
                            {fieldError('tanggal_lahir')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Jenis Kelamin')}</span>
                            <FormSelect value={data.jenis_kelamin} options={[{ value: 'Laki-laki', label: 'Laki-laki' }, { value: 'Perempuan', label: 'Perempuan' }]} onChange={(value) => setData('jenis_kelamin', value)} placeholder="Pilih Jenis Kelamin" error={errors.jenis_kelamin} className="w-full" />
                            {fieldError('jenis_kelamin')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Agama</span>
                            <FormSelect value={data.agama || ''} options={agamaOptions} onChange={(value) => setData('agama', value)} placeholder="Pilih Agama" error={errors.agama} isClearable className="w-full" />
                            {fieldError('agama')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Golongan Darah</span>
                            <FormSelect value={data.golongan_darah || ''} options={[{ value: 'A', label: 'A' }, { value: 'B', label: 'B' }, { value: 'AB', label: 'AB' }, { value: 'O', label: 'O' }]} onChange={(value) => setData('golongan_darah', value)} placeholder="Pilih Golongan Darah" error={errors.golongan_darah} isClearable className="w-full" />
                            {fieldError('golongan_darah')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Status Perkawinan</span>
                            <FormSelect value={data.status_perkawinan || ''} options={[{ value: 'Belum Kawin', label: 'Belum Kawin' }, { value: 'Kawin', label: 'Kawin' }, { value: 'Cerai Hidup', label: 'Cerai Hidup' }, { value: 'Cerai Mati', label: 'Cerai Mati' }]} onChange={(value) => setData('status_perkawinan', value)} placeholder="Pilih Status Perkawinan" error={errors.status_perkawinan} isClearable className="w-full" />
                            {fieldError('status_perkawinan')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Pendidikan</span>
                            <FormSelect value={data.pendidikan_id || ''} options={pendidikans.map((item) => ({ value: String(item.id), label: item.nama }))} onChange={(value) => setData('pendidikan_id', value)} placeholder="Pilih Pendidikan" error={errors.pendidikan_id} isClearable className="w-full" />
                            {fieldError('pendidikan_id')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Pekerjaan</span>
                            <FormSelect value={data.pekerjaan_id || ''} options={pekerjaans.map((item) => ({ value: String(item.id), label: item.nama }))} onChange={(value) => setData('pekerjaan_id', value)} placeholder="Pilih Pekerjaan" error={errors.pekerjaan_id} isClearable className="w-full" />
                            {fieldError('pekerjaan_id')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Kewarganegaraan')}</span>
                            <input value={data.kewarganegaraan} onChange={(event) => setData('kewarganegaraan', event.target.value)} className={inputClass('kewarganegaraan')} />
                            {fieldError('kewarganegaraan')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Nama Ayah</span>
                            <input value={data.nama_ayah || ''} onChange={(event) => setData('nama_ayah', event.target.value)} className={inputClass('nama_ayah')} />
                            {fieldError('nama_ayah')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Nama Ibu</span>
                            <input value={data.nama_ibu || ''} onChange={(event) => setData('nama_ibu', event.target.value)} className={inputClass('nama_ibu')} />
                            {fieldError('nama_ibu')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Telepon</span>
                            <input value={data.telepon || ''} onChange={(event) => setData('telepon', event.target.value)} className={inputClass('telepon')} />
                            {fieldError('telepon')}
                        </label>

                        <label className="block md:col-span-2">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Alamat</span>
                            <textarea value={data.alamat || ''} onChange={(event) => setData('alamat', event.target.value)} rows={3} className={inputClass('alamat', 'w-full rounded-md border px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100')}></textarea>
                            {fieldError('alamat')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Dusun')}</span>
                            <FormSelect value={data.dusun_id || ''} options={dusuns.map((item) => ({ value: String(item.id), label: item.nama }))} onChange={handleDusunChange} placeholder="Pilih Dusun" error={errors.dusun_id} isClearable className="w-full" />
                            {fieldError('dusun_id')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">RW (opsional)</span>
                            <FormSelect value={data.rw_id || ''} options={filteredRws.map((item) => ({ value: String(item.id), label: `RW ${item.nomor}` }))} onChange={handleRwChange} placeholder="Pilih RW" error={errors.rw_id} isClearable className="w-full" />
                            {fieldError('rw_id')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">RT (opsional)</span>
                            <FormSelect value={data.rt_id || ''} options={filteredRts.map((item) => ({ value: String(item.id), label: `RT ${item.nomor}` }))} onChange={(value) => setData('rt_id', value)} placeholder="Pilih RT" error={errors.rt_id} isClearable className="w-full" />
                            {fieldError('rt_id')}
                        </label>

                        <label className="block">
                            <span>{requiredLabel('Status Penduduk')}</span>
                            <FormSelect value={data.status_penduduk} options={[{ value: 'aktif', label: 'Aktif' }, { value: 'pindah', label: 'Pindah' }, { value: 'meninggal', label: 'Meninggal' }, { value: 'sementara', label: 'Sementara' }]} onChange={(value) => setData('status_penduduk', value)} placeholder="Pilih Status Penduduk" error={errors.status_penduduk} className="w-full" />
                            {fieldError('status_penduduk')}
                        </label>

                        <label className="flex items-center gap-3 self-end rounded-md border border-slate-200 p-3">
                            <input type="checkbox" checked={Boolean(data.masih_hidup)} onChange={(event) => setData('masih_hidup', event.target.checked)} className="size-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" />
                            <span className="text-sm font-medium text-slate-700">Masih Hidup</span>
                        </label>
                    </div>

                    {!editing && <div className="space-y-5 border-t border-slate-100 pt-6">
                        <div><h2 className="font-semibold text-slate-800">Kartu Keluarga</h2><p className="text-sm text-slate-500">Pilih KK yang sudah ada atau isi nomor KK baru. Penduduk utama menjadi Kepala Keluarga.</p></div>
                        <div className="grid gap-5 md:grid-cols-2">
                            <label><span className="mb-2 block text-sm font-medium text-slate-700">KK yang sudah ada</span><FormSelect value={data.kartu_keluarga_id} options={kartuKeluargas.map((item) => ({ value: String(item.id), label: `${item.nomor_kk} - ${item.kepala_keluarga?.penduduk?.nama_lengkap || 'Belum ada kepala'} - ${item.alamat || '-'}` }))} onChange={(value) => { setData('kartu_keluarga_id', value); setData('hubungan_keluarga_utama', value ? 'Lainnya' : 'Kepala Keluarga'); if (value) setData('nomor_kk', ''); }} placeholder="Tanpa KK / buat KK baru" isClearable /></label>
                            <label><span className="mb-2 block text-sm font-medium text-slate-700">Hubungan Penduduk Utama</span><FormSelect value={data.hubungan_keluarga_utama} options={['Kepala Keluarga','Suami','Istri','Anak','Menantu','Cucu','Orang Tua','Mertua','Famili Lain','Lainnya'].filter((value) => !data.kartu_keluarga_id || value !== 'Kepala Keluarga').map((value) => ({ value, label: value }))} onChange={(value) => setData('hubungan_keluarga_utama', value)} /></label>
                            {!data.kartu_keluarga_id && <><label><span className="mb-2 block text-sm font-medium text-slate-700">Nomor KK Baru</span><input value={data.nomor_kk} onChange={(event) => setData('nomor_kk', event.target.value)} className={inputClass('nomor_kk')} />{fieldError('nomor_kk')}</label><label><span className="mb-2 block text-sm font-medium text-slate-700">Tanggal Diterbitkan</span><input type="date" value={data.tanggal_diterbitkan} onChange={(event) => setData('tanggal_diterbitkan', event.target.value)} className={inputClass('tanggal_diterbitkan')} /></label></>}
                        </div>
                        <div className="flex items-center justify-between"><div><h2 className="font-semibold text-slate-800">Anggota Keluarga Tambahan</h2><p className="text-sm text-slate-500">Alamat, Dusun, RW, dan RT mengikuti data penduduk utama.</p></div><button type="button" onClick={addPenduduk} className="inline-flex items-center gap-2 rounded-md bg-cyan-50 px-3 py-2 text-sm font-semibold text-cyan-700"><Plus className="size-4" /> Tambah Penduduk</button></div>
                        {data.penduduks.map((item, index) => <div key={index} className="grid gap-4 rounded-lg border border-slate-200 p-4 md:grid-cols-2"><div className="flex items-center justify-between md:col-span-2"><strong>Anggota {index + 1}</strong><button type="button" onClick={() => removePenduduk(index)} className="text-red-600"><Trash2 className="size-4" /></button></div><label><span>{requiredLabel('NIK')}</span><input value={item.nik} onChange={(event) => updatePenduduk(index, 'nik', event.target.value)} className={inputClass(`penduduks.${index}.nik`)} />{fieldError(`penduduks.${index}.nik`)}</label><label><span>{requiredLabel('Nama Lengkap')}</span><input value={item.nama_lengkap} onChange={(event) => updatePenduduk(index, 'nama_lengkap', event.target.value)} className={inputClass(`penduduks.${index}.nama_lengkap`)} />{fieldError(`penduduks.${index}.nama_lengkap`)}</label><label><span className="mb-2 block text-sm font-medium text-slate-700">Tempat Lahir</span><input value={item.tempat_lahir} onChange={(event) => updatePenduduk(index, 'tempat_lahir', event.target.value)} className={inputClass(`penduduks.${index}.tempat_lahir`)} /></label><label><span className="mb-2 block text-sm font-medium text-slate-700">Tanggal Lahir</span><input type="date" value={item.tanggal_lahir} onChange={(event) => updatePenduduk(index, 'tanggal_lahir', event.target.value)} className={inputClass(`penduduks.${index}.tanggal_lahir`)} /></label><label><span>{requiredLabel('Jenis Kelamin')}</span><FormSelect value={item.jenis_kelamin} options={[{ value: 'Laki-laki', label: 'Laki-laki' }, { value: 'Perempuan', label: 'Perempuan' }]} onChange={(value) => updatePenduduk(index, 'jenis_kelamin', value)} /></label><label><span>{requiredLabel('Hubungan Keluarga')}</span><FormSelect value={item.hubungan_keluarga} options={['Suami','Istri','Anak','Menantu','Cucu','Orang Tua','Mertua','Famili Lain','Lainnya'].map((value) => ({ value, label: value }))} onChange={(value) => updatePenduduk(index, 'hubungan_keluarga', value)} /></label></div>)}
                    </div>}

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href={editing ? `/penduduk/${penduduk.id}` : '/penduduk'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan'}</button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
