import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Save } from "lucide-react";
import FormSelect from "@/components/form-select";
import VillageDashboardLayout from "@/layouts/village-dashboard-layout";
import { labelKartuKeluarga } from "@/lib/kartu-keluarga";

const defaultForm = {
  kartu_keluarga_id: "",
  ayah_id: "",
  ibu_id: "",
  nama_lengkap: "",
  nik: "",
  jenis_kelamin: "",
  tempat_lahir: "",
  tanggal_lahir: "",
  waktu_lahir: "",
  golongan_darah: "",
  nomor_akta_kelahiran: "",
  tanggal_akta_kelahiran: "",
  anak_ke: "",
  berat_lahir: "",
  panjang_lahir: "",
  tanggal_dilaporkan: "",
  catatan: "",
};

export default function KelahiranForm({
  kelahiran,
  penduduk = [],
  kartuKeluargas = [],
}) {
  const editing = Boolean(kelahiran);
  const { data, setData, post, put, processing, errors } = useForm({
    ...defaultForm,
    ...(kelahiran || {}),
  });

  const selectedKartuKeluargaId = String(data.kartu_keluarga_id || "");

  const memberOptionsByKk = (gender) => {
    if (!selectedKartuKeluargaId) {
      return [];
    }

    return penduduk
      .filter((item) => item.jenis_kelamin === gender)
      .filter((item) => {
        const memberships = Array.isArray(item.anggota_kartu_keluarga)
          ? item.anggota_kartu_keluarga
          : [];
        return memberships.some(
          (membership) =>
            String(membership.kartu_keluarga_id) === selectedKartuKeluargaId &&
            membership.aktif !== false,
        );
      })
      .map((item) => ({
        value: String(item.id),
        label: `${item.nik || "-"} - ${item.nama_lengkap}`,
      }));
  };

  const ayahOptions = memberOptionsByKk("Laki-laki");
  const ibuOptions = memberOptionsByKk("Perempuan");

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
    editing ? put(`/kelahiran/${kelahiran.id}`) : post("/kelahiran");
  };

  return (
    <VillageDashboardLayout>
      <Head title={editing ? "Edit Kelahiran" : "Tambah Kelahiran"} />
      <section className="mx-auto max-w-8xl space-y-5">
        <div className="flex items-center gap-3">
          <Link
            href={editing ? `/kelahiran/${kelahiran.id}` : "/kelahiran"}
            className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"
          >
            <ArrowLeft className="size-5" />
          </Link>
          <div>
            <p className="text-sm font-medium text-cyan-600">
              Peristiwa Kependudukan
            </p>
            <h1 className="text-2xl font-bold text-slate-800">
              {editing ? "Edit Kelahiran" : "Tambah Kelahiran"}
            </h1>
          </div>
        </div>

        <form
          onSubmit={submit}
          className="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7"
        >
          <div className="space-y-5">
            <div className="rounded-md border border-slate-200 bg-slate-50 p-4">
              <h2 className="mb-4 text-lg font-bold text-slate-800">
                Kartu Keluarga
              </h2>
              <div className="grid gap-5 md:grid-cols-2">
                <label className="block md:col-span-2">
                  <span>{requiredLabel("Kartu Keluarga")}</span>
                  <FormSelect
                    value={data.kartu_keluarga_id || ""}
                    options={kartuKeluargas.map((item) => ({
                      value: String(item.id),
                      label: labelKartuKeluarga(item),
                    }))}
                    onChange={(value) => setData("kartu_keluarga_id", value)}
                    placeholder="Pilih kartu keluarga"
                    error={errors.kartu_keluarga_id}
                    className="w-full"
                  />
                  {fieldError("kartu_keluarga_id")}
                </label>
              </div>
            </div>

            <div className="rounded-md border border-slate-200 bg-slate-50 p-4">
              <h2 className="mb-4 text-lg font-bold text-slate-800">
                Orang Tua
              </h2>
              <div className="grid gap-5 md:grid-cols-2">
                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Ayah
                  </span>
                  <FormSelect
                    value={data.ayah_id || ""}
                    options={ayahOptions}
                    onChange={(value) => setData("ayah_id", value)}
                    placeholder={
                      selectedKartuKeluargaId
                        ? "Pilih ayah dari KK ini"
                        : "Pilih kartu keluarga terlebih dahulu"
                    }
                    error={errors.ayah_id}
                    isClearable
                    className="w-full"
                  />
                  {fieldError("ayah_id")}
                </label>

                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Ibu
                  </span>
                  <FormSelect
                    value={data.ibu_id || ""}
                    options={ibuOptions}
                    onChange={(value) => setData("ibu_id", value)}
                    placeholder={
                      selectedKartuKeluargaId
                        ? "Pilih ibu dari KK ini"
                        : "Pilih kartu keluarga terlebih dahulu"
                    }
                    error={errors.ibu_id}
                    isClearable
                    className="w-full"
                  />
                  {fieldError("ibu_id")}
                </label>
              </div>
            </div>

            <div className="rounded-md border border-slate-200 bg-slate-50 p-4">
              <h2 className="mb-4 text-lg font-bold text-slate-800">
                Data Bayi
              </h2>
              <div className="grid gap-5 md:grid-cols-2">
                <label className="block md:col-span-2">
                  <span>{requiredLabel("Nama Lengkap")}</span>
                  <input
                    value={data.nama_lengkap || ""}
                    onChange={(event) =>
                      setData("nama_lengkap", event.target.value)
                    }
                    className={inputClass("nama_lengkap")}
                  />
                  {fieldError("nama_lengkap")}
                </label>

                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    NIK
                  </span>
                  <input
                    value={data.nik || ""}
                    onChange={(event) => setData("nik", event.target.value)}
                    className={inputClass("nik")}
                  />
                  {fieldError("nik")}
                </label>

                <label className="block">
                  <span>{requiredLabel("Jenis Kelamin")}</span>
                  <FormSelect
                    value={data.jenis_kelamin || ""}
                    options={[
                      { value: "Laki-laki", label: "Laki-laki" },
                      { value: "Perempuan", label: "Perempuan" },
                    ]}
                    onChange={(value) => setData("jenis_kelamin", value)}
                    placeholder="Pilih jenis kelamin"
                    error={errors.jenis_kelamin}
                    className="w-full"
                  />
                  {fieldError("jenis_kelamin")}
                </label>

                <label className="block">
                  <span>{requiredLabel("Tempat Lahir")}</span>
                  <input
                    value={data.tempat_lahir || ""}
                    onChange={(event) =>
                      setData("tempat_lahir", event.target.value)
                    }
                    className={inputClass("tempat_lahir")}
                  />
                  {fieldError("tempat_lahir")}
                </label>

                <label className="block">
                  <span>{requiredLabel("Tanggal Lahir")}</span>
                  <input
                    type="date"
                    value={data.tanggal_lahir || ""}
                    onChange={(event) =>
                      setData("tanggal_lahir", event.target.value)
                    }
                    className={inputClass("tanggal_lahir")}
                  />
                  {fieldError("tanggal_lahir")}
                </label>

                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Waktu Lahir
                  </span>
                  <input
                    type="time"
                    step="1"
                    value={data.waktu_lahir || ""}
                    onChange={(event) =>
                      setData("waktu_lahir", event.target.value)
                    }
                    className={inputClass("waktu_lahir")}
                  />
                  {fieldError("waktu_lahir")}
                </label>

                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Golongan Darah
                  </span>
                  <FormSelect
                    value={data.golongan_darah || ""}
                    options={[
                      { value: "A", label: "A" },
                      { value: "B", label: "B" },
                      { value: "AB", label: "AB" },
                      { value: "O", label: "O" },
                    ]}
                    onChange={(value) => setData("golongan_darah", value)}
                    placeholder="Pilih golongan darah"
                    error={errors.golongan_darah}
                    isClearable
                    className="w-full"
                  />
                  {fieldError("golongan_darah")}
                </label>
              </div>
            </div>

            <div className="rounded-md border border-slate-200 bg-slate-50 p-4">
              <h2 className="mb-4 text-lg font-bold text-slate-800">
                Data Kelahiran
              </h2>
              <div className="grid gap-5 md:grid-cols-2">
                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Nomor Akta Kelahiran
                  </span>
                  <input
                    value={data.nomor_akta_kelahiran || ""}
                    onChange={(event) =>
                      setData("nomor_akta_kelahiran", event.target.value)
                    }
                    className={inputClass("nomor_akta_kelahiran")}
                  />
                  {fieldError("nomor_akta_kelahiran")}
                </label>

                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Tanggal Akta
                  </span>
                  <input
                    type="date"
                    value={data.tanggal_akta_kelahiran || ""}
                    onChange={(event) =>
                      setData("tanggal_akta_kelahiran", event.target.value)
                    }
                    className={inputClass("tanggal_akta_kelahiran")}
                  />
                  {fieldError("tanggal_akta_kelahiran")}
                </label>

                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Anak Ke
                  </span>
                  <input
                    type="number"
                    min="1"
                    value={data.anak_ke || ""}
                    onChange={(event) => setData("anak_ke", event.target.value)}
                    className={inputClass("anak_ke")}
                  />
                  {fieldError("anak_ke")}
                </label>

                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Berat Lahir (kg)
                  </span>
                  <input
                    type="number"
                    step="0.01"
                    value={data.berat_lahir || ""}
                    onChange={(event) =>
                      setData("berat_lahir", event.target.value)
                    }
                    className={inputClass("berat_lahir")}
                  />
                  {fieldError("berat_lahir")}
                </label>

                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Panjang Lahir (cm)
                  </span>
                  <input
                    type="number"
                    step="0.01"
                    value={data.panjang_lahir || ""}
                    onChange={(event) =>
                      setData("panjang_lahir", event.target.value)
                    }
                    className={inputClass("panjang_lahir")}
                  />
                  {fieldError("panjang_lahir")}
                </label>

                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Tanggal Dilaporkan
                  </span>
                  <input
                    type="date"
                    value={data.tanggal_dilaporkan || ""}
                    onChange={(event) =>
                      setData("tanggal_dilaporkan", event.target.value)
                    }
                    className={inputClass("tanggal_dilaporkan")}
                  />
                  {fieldError("tanggal_dilaporkan")}
                </label>

                <label className="block md:col-span-2">
                  <span className="mb-2 block text-sm font-medium text-slate-700">
                    Catatan
                  </span>
                  <textarea
                    value={data.catatan || ""}
                    onChange={(event) => setData("catatan", event.target.value)}
                    rows={3}
                    className={`${inputClass("catatan", "w-full rounded-md border px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100")} ${errors.catatan ? "border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100" : "border-slate-200 bg-white"}`}
                  />
                  {fieldError("catatan")}
                </label>
              </div>
            </div>
          </div>

          <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <Link
              href={editing ? `/kelahiran/${kelahiran.id}` : "/kelahiran"}
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
