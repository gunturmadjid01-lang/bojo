import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

const defaultForm = {
    penduduk_id: '',
    status_pekerjaan: '',
    penghasilan_bulanan: '',
    kategori_penghasilan: '',
    jaminan_kesehatan: '',
    tanggal_pendataan: '',
};

export default function SosialEkonomiPendudukForm({ sosialEkonomiPenduduk, penduduks = [] }) {
    const editing = Boolean(sosialEkonomiPenduduk);
    const { data, setData, post, put, processing, errors } = useForm({
        ...defaultForm,
        ...(sosialEkonomiPenduduk || {}),
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
        editing ? put(`/sosial-ekonomi-penduduk/${sosialEkonomiPenduduk.id}`) : post('/sosial-ekonomi-penduduk');
    };

    return (
        <VillageDashboardLayout>
            <Head title={editing ? 'Edit Sosial Ekonomi Penduduk' : 'Tambah Sosial Ekonomi Penduduk'} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={editing ? `/sosial-ekonomi-penduduk/${sosialEkonomiPenduduk.id}` : '/sosial-ekonomi-penduduk'} className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link>
                    <div>
                        <p className="text-sm font-medium text-cyan-600">Data Sosial</p>
                        <h1 className="text-2xl font-bold text-slate-800">{editing ? 'Edit Sosial Ekonomi Penduduk' : 'Tambah Sosial Ekonomi Penduduk'}</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div className="grid gap-5 md:grid-cols-2">
                        <label className="block">
                            <span>{requiredLabel('Penduduk')}</span>
                            <FormSelect value={data.penduduk_id || ''} options={penduduks.map((item) => ({ value: String(item.id), label: `${item.nama_lengkap} (${item.nik})` }))} onChange={(value) => setData('penduduk_id', value)} placeholder="Pilih penduduk" error={errors.penduduk_id} className="w-full" />
                            {fieldError('penduduk_id')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Tanggal Pendataan</span>
                            <input type="date" value={data.tanggal_pendataan || ''} onChange={(event) => setData('tanggal_pendataan', event.target.value)} className={inputClass('tanggal_pendataan')} />
                            {fieldError('tanggal_pendataan')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Status Pekerjaan</span>
                            <input value={data.status_pekerjaan || ''} onChange={(event) => setData('status_pekerjaan', event.target.value)} className={inputClass('status_pekerjaan')} />
                            {fieldError('status_pekerjaan')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Penghasilan Bulanan</span>
                            <input type="number" step="0.01" min="0" value={data.penghasilan_bulanan || ''} onChange={(event) => setData('penghasilan_bulanan', event.target.value)} className={inputClass('penghasilan_bulanan')} />
                            {fieldError('penghasilan_bulanan')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Kategori Penghasilan</span>
                            <input value={data.kategori_penghasilan || ''} onChange={(event) => setData('kategori_penghasilan', event.target.value)} className={inputClass('kategori_penghasilan')} />
                            {fieldError('kategori_penghasilan')}
                        </label>

                        <label className="block">
                            <span className="mb-2 block text-sm font-medium text-slate-700">Jaminan Kesehatan</span>
                            <input value={data.jaminan_kesehatan || ''} onChange={(event) => setData('jaminan_kesehatan', event.target.value)} className={inputClass('jaminan_kesehatan')} />
                            {fieldError('jaminan_kesehatan')}
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <Link href={editing ? `/sosial-ekonomi-penduduk/${sosialEkonomiPenduduk.id}` : '/sosial-ekonomi-penduduk'} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</Link>
                        <button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"><Save className="size-4" /> {processing ? 'Menyimpan...' : 'Simpan'}</button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
