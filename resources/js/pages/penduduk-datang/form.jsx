import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Save } from "lucide-react";
import { useMemo } from "react";
import FormSelect from "@/components/form-select";
import VillageDashboardLayout from "@/layouts/village-dashboard-layout";

export default function PendudukDatangForm({ dusuns = [], rws = [], rts = [], pendidikans = [], pekerjaans = [] }) {
  const { data, setData, post, processing, errors } = useForm({
    tanggal_datang: "", alamat_sebelumnya: "", desa_sebelumnya: "", kecamatan_sebelumnya: "", kabupaten_sebelumnya: "", provinsi_sebelumnya: "", nomor_surat_pindah: "", alasan: "", catatan: "",
    penduduk: { nik: "", nama_lengkap: "", tempat_lahir: "", tanggal_lahir: "", jenis_kelamin: "Laki-laki", agama: "", golongan_darah: "", status_perkawinan: "", pendidikan_id: "", pekerjaan_id: "", kewarganegaraan: "WNI", nama_ayah: "", nama_ibu: "", telepon: "", email: "", alamat: "", dusun_id: "", rw_id: "", rt_id: "" },
  });
  const person = data.penduduk;
  const setPerson = (key, value) => setData("penduduk", { ...person, [key]: value, ...(key === "dusun_id" ? { rw_id: "", rt_id: "" } : {}), ...(key === "rw_id" ? { rt_id: "" } : {}) });
  const filteredRws = useMemo(() => rws.filter((row) => !person.dusun_id || String(row.dusun_id) === String(person.dusun_id)), [rws, person.dusun_id]);
  const filteredRts = useMemo(() => rts.filter((row) => !person.rw_id || String(row.rw_id) === String(person.rw_id)), [rts, person.rw_id]);
  const inputClass = "h-11 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100";
  const label = (text) => <span className="mb-2 block text-sm font-medium text-slate-700">{text}</span>;
  const error = (key) => errors[key] && <p className="mt-1 text-xs text-red-600">{errors[key]}</p>;
  const personFields = [["nik", "NIK *"], ["nama_lengkap", "Nama Lengkap *"], ["tempat_lahir", "Tempat Lahir"], ["tanggal_lahir", "Tanggal Lahir", "date"], ["agama", "Agama"], ["golongan_darah", "Golongan Darah"], ["status_perkawinan", "Status Perkawinan"], ["kewarganegaraan", "Kewarganegaraan"], ["nama_ayah", "Nama Ayah"], ["nama_ibu", "Nama Ibu"], ["telepon", "Telepon"], ["email", "Email", "email"]];
  const originFields = [["alamat_sebelumnya", "Alamat Sebelumnya"], ["desa_sebelumnya", "Desa/Kelurahan Sebelumnya"], ["kecamatan_sebelumnya", "Kecamatan Sebelumnya"], ["kabupaten_sebelumnya", "Kabupaten/Kota Sebelumnya"], ["provinsi_sebelumnya", "Provinsi Sebelumnya"], ["nomor_surat_pindah", "Nomor Surat Pindah"], ["alasan", "Alasan"]];
  return <VillageDashboardLayout>
    <Head title="Tambah Penduduk Masuk" />
    <section className="mx-auto max-w-5xl space-y-5">
      <div className="flex items-center gap-3"><Link href="/penduduk-datang" className="rounded-md p-2 text-slate-500 hover:text-cyan-600"><ArrowLeft className="size-5" /></Link><div><p className="text-sm font-medium text-cyan-600">Peristiwa Kependudukan</p><h1 className="text-2xl font-bold text-slate-800">Tambah Penduduk Masuk</h1></div></div>
      <form onSubmit={(event) => { event.preventDefault(); post("/penduduk-datang"); }} className="space-y-7 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <p className="rounded-md bg-cyan-50 p-3 text-sm text-cyan-800">Satu formulir menambahkan satu penduduk baru dengan status aktif. Keanggotaan Kartu Keluarga tidak diubah dari sini.</p>
        <section><h2 className="mb-4 text-lg font-semibold text-slate-800">Data Kedatangan</h2><div className="grid gap-4 md:grid-cols-2"><label>{label("Tanggal Datang *")}<input type="date" value={data.tanggal_datang} onChange={(e) => setData("tanggal_datang", e.target.value)} className={inputClass} />{error("tanggal_datang")}</label>{originFields.map(([key, title]) => <label key={key}>{label(title)}<input value={data[key]} onChange={(e) => setData(key, e.target.value)} className={inputClass} />{error(key)}</label>)}</div></section>
        <section><h2 className="mb-4 text-lg font-semibold text-slate-800">Penduduk Baru</h2><div className="grid gap-4 md:grid-cols-2">
          {personFields.map(([key, title, type = "text"]) => <label key={key}>{label(title)}<input type={type} value={person[key]} onChange={(e) => setPerson(key, e.target.value)} className={inputClass} />{error(`penduduk.${key}`)}</label>)}
          <label>{label("Jenis Kelamin *")}<FormSelect value={person.jenis_kelamin} options={["Laki-laki", "Perempuan"].map((value) => ({ value, label: value }))} onChange={(value) => setPerson("jenis_kelamin", value)} />{error("penduduk.jenis_kelamin")}</label>
          <label>{label("Pendidikan")}<FormSelect value={person.pendidikan_id} options={pendidikans.map((row) => ({ value: String(row.id), label: row.nama }))} onChange={(value) => setPerson("pendidikan_id", value)} isClearable />{error("penduduk.pendidikan_id")}</label>
          <label>{label("Pekerjaan")}<FormSelect value={person.pekerjaan_id} options={pekerjaans.map((row) => ({ value: String(row.id), label: row.nama }))} onChange={(value) => setPerson("pekerjaan_id", value)} isClearable />{error("penduduk.pekerjaan_id")}</label>
          <label>{label("Dusun *")}<FormSelect value={person.dusun_id} options={dusuns.map((row) => ({ value: String(row.id), label: row.nama }))} onChange={(value) => setPerson("dusun_id", value)} />{error("penduduk.dusun_id")}</label>
          <label>{label("RW (opsional)")}<FormSelect value={person.rw_id} options={filteredRws.map((row) => ({ value: String(row.id), label: `RW ${row.nomor}` }))} onChange={(value) => setPerson("rw_id", value || "")} placeholder="Pilih jika diketahui" isClearable />{error("penduduk.rw_id")}</label>
          <label>{label("RT (opsional)")}<FormSelect value={person.rt_id} options={filteredRts.map((row) => ({ value: String(row.id), label: `RT ${row.nomor}` }))} onChange={(value) => setPerson("rt_id", value || "")} placeholder="Pilih jika diketahui" isClearable />{error("penduduk.rt_id")}</label>
          <label className="md:col-span-2">{label("Alamat Sekarang *")}<textarea value={person.alamat} onChange={(e) => setPerson("alamat", e.target.value)} rows={3} className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />{error("penduduk.alamat")}</label>
          <label className="md:col-span-2">{label("Catatan")}<textarea value={data.catatan} onChange={(e) => setData("catatan", e.target.value)} rows={3} className="w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />{error("catatan")}</label>
        </div></section>
        <div className="flex justify-end gap-3 border-t border-slate-100 pt-5"><Link href="/penduduk-datang" className="rounded-md border border-slate-200 px-4 py-2.5 text-sm text-slate-600">Batal</Link><button disabled={processing} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"><Save className="size-4" /> Simpan Penduduk Masuk</button></div>
      </form>
    </section>
  </VillageDashboardLayout>;
}
