import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Save } from "lucide-react";
import FormSelect from "@/components/form-select";
import VillageDashboardLayout from "@/layouts/village-dashboard-layout";

export default function PendudukPindahForm({ penduduk = [] }) {
  const { data, setData, post, processing, errors } = useForm({ penduduk_id: "", tanggal_pindah: "", alamat_tujuan: "", desa_tujuan: "", kecamatan_tujuan: "", kabupaten_tujuan: "", provinsi_tujuan: "", nomor_surat_pindah: "", alasan: "", catatan: "" });
  const inputClass = "h-11 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100";
  const label = (text) => <span className="mb-2 block text-sm font-medium text-slate-700">{text}</span>;
  const error = (key) => errors[key] && <p className="mt-1 text-xs text-red-600">{errors[key]}</p>;
  const fields = [["alamat_tujuan", "Alamat Tujuan"], ["desa_tujuan", "Desa/Kelurahan Tujuan"], ["kecamatan_tujuan", "Kecamatan Tujuan"], ["kabupaten_tujuan", "Kabupaten/Kota Tujuan"], ["provinsi_tujuan", "Provinsi Tujuan"], ["nomor_surat_pindah", "Nomor Surat Pindah"], ["alasan", "Alasan Pindah"]];
  return <VillageDashboardLayout>
    <Head title="Tambah Penduduk Keluar" />
    <section className="mx-auto max-w-4xl space-y-5">
      <div className="flex items-center gap-3"><Link href="/penduduk-pindah" className="rounded-md p-2 text-slate-500 hover:text-cyan-600"><ArrowLeft className="size-5" /></Link><div><p className="text-sm font-medium text-cyan-600">Peristiwa Kependudukan</p><h1 className="text-2xl font-bold text-slate-800">Tambah Penduduk Keluar</h1></div></div>
      <form onSubmit={(event) => { event.preventDefault(); post("/penduduk-pindah"); }} className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <p className="rounded-md bg-cyan-50 p-3 text-sm text-cyan-800">Pilih satu penduduk. Setelah disimpan, statusnya menjadi pindah tanpa mengubah data Kartu Keluarga.</p>
        <div className="grid gap-4 md:grid-cols-2">
          <label className="md:col-span-2">{label("Penduduk *")}<FormSelect value={data.penduduk_id} options={penduduk.map((item) => ({ value: String(item.id), label: `${item.nik || "-"} - ${item.nama_lengkap}` }))} onChange={(value) => setData("penduduk_id", value)} placeholder="Cari dan pilih penduduk" error={errors.penduduk_id} />{error("penduduk_id")}</label>
          <label>{label("Tanggal Pindah *")}<input type="date" value={data.tanggal_pindah} onChange={(event) => setData("tanggal_pindah", event.target.value)} className={inputClass} />{error("tanggal_pindah")}</label>
          {fields.map(([key, title]) => <label key={key}>{label(title)}<input value={data[key]} onChange={(event) => setData(key, event.target.value)} className={inputClass} />{error(key)}</label>)}
          <label className="md:col-span-2">{label("Catatan")}<textarea value={data.catatan} onChange={(event) => setData("catatan", event.target.value)} rows={3} className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />{error("catatan")}</label>
        </div>
        <div className="flex justify-end gap-3 border-t border-slate-100 pt-5"><Link href="/penduduk-pindah" className="rounded-md border border-slate-200 px-4 py-2.5 text-sm text-slate-600">Batal</Link><button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"><Save className="size-4" /> Simpan Penduduk Keluar</button></div>
      </form>
    </section>
  </VillageDashboardLayout>;
}
