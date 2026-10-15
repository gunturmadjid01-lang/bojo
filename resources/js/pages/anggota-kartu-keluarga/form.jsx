import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Save } from "lucide-react";
import FormSelect from "@/components/form-select";
import VillageDashboardLayout from "@/layouts/village-dashboard-layout";
import { labelKartuKeluarga } from "@/lib/kartu-keluarga";

const defaultForm = {
  kartu_keluarga_id: "",
  penduduk_id: "",
  hubungan_keluarga: "",
  tanggal_masuk: "",
  tanggal_keluar: "",
  aktif: true,
  alasan_keluar: "",
};

export default function AnggotaKartuKeluargaForm({
  anggotaKartuKeluarga,
  kartuKeluargas = [],
  penduduk = [],
}) {
  const editing = Boolean(anggotaKartuKeluarga);
  const { data, setData, post, put, processing, errors } = useForm({
    ...defaultForm,
    ...(anggotaKartuKeluarga || {}),
  });

  const inputClass = (
    field,
    baseClass = "h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100",
  ) => {
    const errorClass = errors[field]
      ? "border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100"
      : "border-slate-200 bg-white";
    return `${baseClass} ${errorClass}`;
  };

  const requiredLabel = (label) => (
    <span className="mb-2 flex items-center gap-1 text-sm font-medium text-slate-700">
      <span>{label}</span>
      <span className="text-red-600">*</span>
    </span>
  );

  const fieldError = (field) =>
    errors[field] && (
      <p className="mt-1 text-xs text-red-600">{errors[field]}</p>
    );

  const submit = (event) => {
    event.preventDefault();
    editing
      ? put(`/anggota-kartu-keluarga/${anggotaKartuKeluarga.id}`)
      : post("/anggota-kartu-keluarga");
  };

  return (
    <VillageDashboardLayout>
      <Head
        title={editing ? "Edit Anggota Keluarga" : "Tambah Anggota Keluarga"}
      />
      <section className="mx-auto max-w-8xl space-y-5">
        <div className="flex items-center gap-3">
          <Link
            href={
              editing
                ? `/anggota-kartu-keluarga/${anggotaKartuKeluarga.id}`
                : "/anggota-kartu-keluarga"
            }
            className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"
          >
            <ArrowLeft className="size-5" />
          </Link>
          <div>
            <p className="text-sm font-medium text-cyan-600">Kependudukan</p>
            <h1 className="text-2xl font-bold text-slate-800">
              {editing ? "Edit Anggota Keluarga" : "Tambah Anggota Keluarga"}
            </h1>
          </div>
        </div>

        <form
          onSubmit={submit}
          className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7"
        >
          <div className="grid gap-5 md:grid-cols-2">
            <label className="block">
              <span>{requiredLabel("Kartu Keluarga")}</span>
              <FormSelect
                value={data.kartu_keluarga_id || ""}
                options={kartuKeluargas.map((item) => ({
                  value: String(item.id),
                  label: labelKartuKeluarga(item),
                }))}
                onChange={(value) => setData("kartu_keluarga_id", value)}
                placeholder="Pilih KK"
                error={errors.kartu_keluarga_id}
                className="w-full"
              />
              {fieldError("kartu_keluarga_id")}
            </label>

            <label className="block">
              <span>{requiredLabel("Penduduk")}</span>
              <FormSelect
                value={data.penduduk_id || ""}
                options={penduduk.map((item) => ({
                  value: String(item.id),
                  label: `${item.nama_lengkap} (${item.nik})`,
                }))}
                onChange={(value) => setData("penduduk_id", value)}
                placeholder="Pilih penduduk"
                error={errors.penduduk_id}
                className="w-full"
              />
              {fieldError("penduduk_id")}
            </label>

            <label className="block">
              <span>{requiredLabel("Hubungan Keluarga")}</span>
              <FormSelect
                value={data.hubungan_keluarga || ""}
                options={[
                  { value: "Kepala Keluarga", label: "Kepala Keluarga" },
                  { value: "Suami", label: "Suami" },
                  { value: "Istri", label: "Istri" },
                  { value: "Anak", label: "Anak" },
                  { value: "Menantu", label: "Menantu" },
                  { value: "Cucu", label: "Cucu" },
                  { value: "Orang Tua", label: "Orang Tua" },
                  { value: "Mertua", label: "Mertua" },
                  { value: "Famili Lain", label: "Famili Lain" },
                  { value: "Lainnya", label: "Lainnya" },
                ]}
                onChange={(value) => setData("hubungan_keluarga", value)}
                placeholder="Pilih hubungan"
                error={errors.hubungan_keluarga}
                className="w-full"
              />
              {fieldError("hubungan_keluarga")}
            </label>

            <label className="block">
              <span className="mb-2 block text-sm font-medium text-slate-700">
                Tanggal Masuk
              </span>
              <input
                type="date"
                value={data.tanggal_masuk || ""}
                onChange={(event) =>
                  setData("tanggal_masuk", event.target.value)
                }
                className={inputClass("tanggal_masuk")}
              />
              {fieldError("tanggal_masuk")}
            </label>

            <label className="block">
              <span className="mb-2 block text-sm font-medium text-slate-700">
                Tanggal Keluar
              </span>
              <input
                type="date"
                value={data.tanggal_keluar || ""}
                onChange={(event) =>
                  setData("tanggal_keluar", event.target.value)
                }
                className={inputClass("tanggal_keluar")}
              />
              {fieldError("tanggal_keluar")}
            </label>

            <label className="block">
              <span className="mb-2 block text-sm font-medium text-slate-700">
                Status Aktif
              </span>
              <FormSelect
                value={String(Boolean(data.aktif))}
                options={[
                  { value: "true", label: "Aktif" },
                  { value: "false", label: "Tidak Aktif" },
                ]}
                onChange={(value) => setData("aktif", value === "true")}
                placeholder="Pilih status"
                error={errors.aktif}
                className="w-full"
              />
              {fieldError("aktif")}
            </label>

            <label className="block md:col-span-2">
              <span className="mb-2 block text-sm font-medium text-slate-700">
                Alasan Keluar
              </span>
              <input
                value={data.alasan_keluar || ""}
                onChange={(event) =>
                  setData("alasan_keluar", event.target.value)
                }
                className={inputClass("alasan_keluar")}
              />
              {fieldError("alasan_keluar")}
            </label>
          </div>

          <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <Link
              href={
                editing
                  ? `/anggota-kartu-keluarga/${anggotaKartuKeluarga.id}`
                  : "/anggota-kartu-keluarga"
              }
              className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
            >
              Batal
            </Link>
            <button
              disabled={processing}
              className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60"
            >
              <Save className="size-4" />{" "}
              {processing ? "Menyimpan..." : "Simpan"}
            </button>
          </div>
        </form>
      </section>
    </VillageDashboardLayout>
  );
}
