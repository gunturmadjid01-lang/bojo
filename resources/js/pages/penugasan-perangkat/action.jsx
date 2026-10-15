import FormSelect from '@/components/form-select';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

export default function ActionPage({ assignment, mode, users = [] }) {
    const replacing = mode === 'replace';
    const { data, setData, post, processing, errors } = useForm(
        replacing
            ? {
                  user_id: '',
                  tanggal_selesai_lama: '',
                  tanggal_mulai: '',
                  tanggal_selesai: '',
                  nomor_sk_pengangkatan: '',
                  tanggal_sk_pengangkatan: '',
                  nomor_sk_pemberhentian: '',
                  tanggal_sk_pemberhentian: '',
                  catatan: '',
              }
            : { tanggal_selesai: '', nomor_sk_pemberhentian: '', tanggal_sk_pemberhentian: '', catatan: '' },
    );
    const submit = (event) => {
        event.preventDefault();
        post(replacing ? `/penugasan-perangkat/${assignment.id}/ganti` : `/penugasan-perangkat/${assignment.id}/akhiri`);
    };
    const input = (name, label, type = 'text') => (
        <label className="block">
            <span className="mb-2 block text-sm font-medium text-slate-700">{label} *</span>
            <input
                required
                type={type}
                value={data[name]}
                onChange={(event) => setData(name, event.target.value)}
                className="h-11 w-full rounded-md border border-slate-200 px-3 text-sm"
            />
            {errors[name] && <small className="text-red-600">{errors[name]}</small>}
        </label>
    );
    return (
        <VillageDashboardLayout>
            <Head title={replacing ? 'Ganti Pejabat' : 'Akhiri Penugasan'} />
            <section className="mx-auto max-w-3xl space-y-5">
                <div>
                    <p className="text-sm font-medium text-cyan-600">Perangkat Desa</p>
                    <h1 className="mt-1 text-2xl font-bold text-slate-800">{replacing ? 'Ganti Pejabat' : 'Akhiri Penugasan'}</h1>
                    <p className="mt-1 text-sm text-slate-500">
                        {assignment.jabatan_desa?.nama} — {assignment.dusun?.nama || assignment.desa?.nama_desa} —{' '}
                        {assignment.perangkat_desa?.nama_lengkap || assignment.perangkat_desa?.user?.name}
                    </p>
                </div>
                <form onSubmit={submit} className="grid gap-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">
                    {replacing ? (
                        <>
                            {input('tanggal_selesai_lama', 'Tanggal Selesai Pejabat Lama', 'date')}
                            <label className="block">
                                <span className="mb-2 block text-sm font-medium text-slate-700">Pejabat Baru *</span>
                                <FormSelect
                                    value={data.user_id}
                                    options={users.map((user) => ({ value: String(user.id), label: `${user.name} (${user.username})` }))}
                                    onChange={(value) => setData('user_id', value)}
                                    placeholder="Pilih pengguna"
                                    error={errors.user_id}
                                />
                            </label>
                            {input('tanggal_mulai', 'Tanggal Mulai Pejabat Baru', 'date')}
                            {input('tanggal_selesai', 'Tanggal Selesai Pejabat Baru', 'date')}
                            {input('nomor_sk_pengangkatan', 'Nomor SK Pengangkatan Baru')}
                            {input('tanggal_sk_pengangkatan', 'Tanggal SK Pengangkatan Baru', 'date')}
                            {input('nomor_sk_pemberhentian', 'Nomor SK Pemberhentian Lama')}
                            {input('tanggal_sk_pemberhentian', 'Tanggal SK Pemberhentian Lama', 'date')}
                        </>
                    ) : (
                        <>
                            {input('tanggal_selesai', 'Tanggal Selesai', 'date')}
                            {input('nomor_sk_pemberhentian', 'Nomor SK Pemberhentian')}
                            {input('tanggal_sk_pemberhentian', 'Tanggal SK Pemberhentian', 'date')}
                        </>
                    )}
                    <label className="block sm:col-span-2">
                        <span className="mb-2 block text-sm font-medium text-slate-700">Catatan</span>
                        <textarea
                            value={data.catatan}
                            onChange={(event) => setData('catatan', event.target.value)}
                            className="w-full rounded-md border border-slate-200 p-3 text-sm"
                            rows="3"
                        />
                    </label>
                    <div className="flex justify-end gap-3 sm:col-span-2">
                        <Link href="/penugasan-perangkat" className="rounded-md border border-slate-200 px-4 py-2.5 text-sm">
                            Batal
                        </Link>
                        <button
                            disabled={processing}
                            className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white"
                        >
                            <Save className="size-4" />
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
