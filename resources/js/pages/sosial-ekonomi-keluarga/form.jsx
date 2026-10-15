import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

const defaultForm = {
    kartu_keluarga_id: '',
    status_kepemilikan_rumah: '',
    kondisi_rumah: '',
    jenis_lantai: '',
    jenis_dinding: '',
    jenis_atap: '',
    sumber_air: '',
    sumber_listrik: '',
    daya_listrik: '',
    fasilitas_toilet: '',
    bahan_bakar_memasak: '',
    memiliki_kendaraan: '',
    akses_internet: '',
    kategori_ekonomi: '',
    tanggal_pendataan: '',
};

export default function SosialEkonomiKeluargaForm({ sosialEkonomiKeluarga, kartuKeluargas = [] }) {
    const editing = Boolean(sosialEkonomiKeluarga);
    const { data, setData, post, put, processing, errors } = useForm({
        ...defaultForm,
        ...(sosialEkonomiKeluarga || {}),
    });

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
        editing ? put(`/sosial-ekonomi-keluarga/${sosialEkonomiKeluarga.id}`) : post('/sosial-ekonomi-keluarga');
    };

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit Sosial Ekonomi Keluarga' : 'Tambah Sosial Ekonomi Keluarga'} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={editing ? `/sosial-ekonomi-keluarga/${sosialEkonomiKeluarga.id}` : '/sosial-ekonomi-keluarga'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Data Sosial</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit Sosial Ekonomi Keluarga' : 'Tambah Sosial Ekonomi Keluarga'}</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="grid gap-5 md:grid-cols-2">
                        <label className="block">
                            <span>{requiredLabel('Kartu Keluarga')}</span>
                            <FormSelect value={data.kartu_keluarga_id || ''} options={kartuKeluargas.map((item) => ({ value: String(item.id), label: `${item.nomor_kk} (${item.alamat || '-'})` }))} onChange={(value) => setData('kartu_keluarga_id', value)} placeholder="Pilih KK" error={errors.kartu_keluarga_id} className="w-full" />
                            {fieldError('kartu_keluarga_id')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Tanggal Pendataan</span>
                            <input type="date" value={data.tanggal_pendataan || ''} onChange={(event) => setData('tanggal_pendataan', event.target.value)} className={inputClass('tanggal_pendataan')} />
                            {fieldError('tanggal_pendataan')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Status Kepemilikan Rumah</span>
                            <input value={data.status_kepemilikan_rumah || ''} onChange={(event) => setData('status_kepemilikan_rumah', event.target.value)} className={inputClass('status_kepemilikan_rumah')} />
                            {fieldError('status_kepemilikan_rumah')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Kondisi Rumah</span>
                            <input value={data.kondisi_rumah || ''} onChange={(event) => setData('kondisi_rumah', event.target.value)} className={inputClass('kondisi_rumah')} />
                            {fieldError('kondisi_rumah')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Jenis Lantai</span>
                            <input value={data.jenis_lantai || ''} onChange={(event) => setData('jenis_lantai', event.target.value)} className={inputClass('jenis_lantai')} />
                            {fieldError('jenis_lantai')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Jenis Dinding</span>
                            <input value={data.jenis_dinding || ''} onChange={(event) => setData('jenis_dinding', event.target.value)} className={inputClass('jenis_dinding')} />
                            {fieldError('jenis_dinding')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Jenis Atap</span>
                            <input value={data.jenis_atap || ''} onChange={(event) => setData('jenis_atap', event.target.value)} className={inputClass('jenis_atap')} />
                            {fieldError('jenis_atap')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Sumber Air</span>
                            <input value={data.sumber_air || ''} onChange={(event) => setData('sumber_air', event.target.value)} className={inputClass('sumber_air')} />
                            {fieldError('sumber_air')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Sumber Listrik</span>
                            <input value={data.sumber_listrik || ''} onChange={(event) => setData('sumber_listrik', event.target.value)} className={inputClass('sumber_listrik')} />
                            {fieldError('sumber_listrik')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Daya Listrik</span>
                            <input value={data.daya_listrik || ''} onChange={(event) => setData('daya_listrik', event.target.value)} className={inputClass('daya_listrik')} />
                            {fieldError('daya_listrik')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Fasilitas Toilet</span>
                            <input value={data.fasilitas_toilet || ''} onChange={(event) => setData('fasilitas_toilet', event.target.value)} className={inputClass('fasilitas_toilet')} />
                            {fieldError('fasilitas_toilet')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Bahan Bakar Memasak</span>
                            <input value={data.bahan_bakar_memasak || ''} onChange={(event) => setData('bahan_bakar_memasak', event.target.value)} className={inputClass('bahan_bakar_memasak')} />
                            {fieldError('bahan_bakar_memasak')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Memiliki Kendaraan</span>
                            <FormSelect value={String(data.memiliki_kendaraan ?? '')} options={[{ value: '1', label: 'Ya' }, { value: '0', label: 'Tidak' }]} onChange={(value) => setData('memiliki_kendaraan', value === '' ? '' : value === '1')} placeholder="Pilih opsi" error={errors.memiliki_kendaraan} isClearable className="w-full" />
                            {fieldError('memiliki_kendaraan')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Akses Internet</span>
                            <FormSelect value={String(data.akses_internet ?? '')} options={[{ value: '1', label: 'Ya' }, { value: '0', label: 'Tidak' }]} onChange={(value) => setData('akses_internet', value === '' ? '' : value === '1')} placeholder="Pilih opsi" error={errors.akses_internet} isClearable className="w-full" />
                            {fieldError('akses_internet')}
                        </label>

                        <label className="block md:col-span-2">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Kategori Ekonomi</span>
                            <input value={data.kategori_ekonomi || ''} onChange={(event) => setData('kategori_ekonomi', event.target.value)} className={inputClass('kategori_ekonomi')} />
                            {fieldError('kategori_ekonomi')}
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href={editing ? `/sosial-ekonomi-keluarga/${sosialEkonomiKeluarga.id}` : '/sosial-ekonomi-keluarga'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan'}</button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
