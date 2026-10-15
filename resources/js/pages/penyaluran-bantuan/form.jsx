import InputUang from '@/components/input-uang';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';
import { Head, Link, useForm } from '@inertiajs/react';
export default function Form({ penerimas = [] }) {
    const { data, setData, post, processing } = useForm({
        penerima_bantuan_id: '',
        periode: '',
        tanggal_penyaluran: '',
        jumlah: '',
        status: 'belum_disalurkan',
        diterima_oleh: '',
        catatan: '',
    });
    const submit = (e) => {
        e.preventDefault();
        post('/penyaluran-bantuan');
    };
    return (
        <VillageDashboardLayout>
            <Head title="Tambah Penyaluran Bantuan" />
            <section className="mx-auto max-w-3xl">
                <h1 className="mb-5 text-2xl font-bold">Tambah Penyaluran Bantuan</h1>
                <form onSubmit={submit} className="grid gap-5 rounded-lg border bg-white p-6 sm:grid-cols-2">
                    <label className="block sm:col-span-2">
                        <span className="mb-2 block text-sm font-medium">Penerima *</span>
                        <select
                            required
                            value={data.penerima_bantuan_id}
                            onChange={(e) => setData('penerima_bantuan_id', e.target.value)}
                            className="h-11 w-full rounded border px-3"
                        >
                            <option value="">Pilih penerima</option>
                            {penerimas.map((x) => (
                                <option key={x.id} value={x.id}>
                                    {x.penduduk?.nama_lengkap || (x.kartu_keluarga?.nomor_kk ? `KK ${x.kartu_keluarga.nomor_kk}` : '-')} —{' '}
                                    {x.program?.nama}
                                </option>
                            ))}
                        </select>
                    </label>
                    {[
                        ['periode', 'Periode', 'text'],
                        ['tanggal_penyaluran', 'Tanggal Penyaluran', 'date'],
                        ['jumlah', 'Jumlah', 'currency'],
                        ['diterima_oleh', 'Diterima Oleh', 'text'],
                    ].map(([n, l, t]) => (
                        <label key={n} className="block">
                            <span className="mb-2 block text-sm font-medium">{l} *</span>
                            {t === 'currency' ? (
                                <InputUang
                                    required
                                    value={data[n]}
                                    onChange={(e) => setData(n, e.target.value)}
                                    className="h-11 w-full rounded border px-3"
                                    placeholder="Rp 0"
                                />
                            ) : (
                                <input
                                    required
                                    type={t}
                                    value={data[n]}
                                    onChange={(e) => setData(n, e.target.value)}
                                    className="h-11 w-full rounded border px-3"
                                />
                            )}
                        </label>
                    ))}
                    <label className="block">
                        <span className="mb-2 block text-sm font-medium">Status *</span>
                        <select
                            required
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="h-11 w-full rounded border px-3"
                        >
                            <option value="belum_disalurkan">Belum Disalurkan</option>
                            <option value="disalurkan">Disalurkan</option>
                            <option value="gagal">Gagal</option>
                        </select>
                    </label>
                    <div className="flex justify-end gap-3 sm:col-span-2">
                        <Link href="/penyaluran-bantuan" className="rounded border px-4 py-2">
                            Batal
                        </Link>
                        <button disabled={processing} className="rounded bg-cyan-600 px-4 py-2 font-semibold text-white">
                            Simpan
                        </button>
                    </div>
                </form>
            </section>
        </VillageDashboardLayout>
    );
}
