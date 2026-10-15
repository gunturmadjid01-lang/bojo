import { Head } from '@inertiajs/react';
import Link from '@/components/permission-link';
import { ArrowLeft, CalendarDays, Pencil, ShieldCheck, UserRound } from 'lucide-react';
import VillageDashboardLayout from '@/layouts/village-dashboard-layout';

export default function UserShow({ user }) {
    return (
        <VillageDashboardLayout>
            <Head title={`Detail ${user.name}`} />
            <section className="mx-auto max-w-8xl space-y-5">
                <div className="flex items-center justify-between gap-3"><div className="flex items-center gap-3"><Link href="/users" className="rounded-md p-2 text-slate-500 hover:bg-white hover:text-cyan-600"><ArrowLeft className="size-5" /></Link><div><p className="text-sm font-medium text-cyan-600">Manajemen Akses</p><h1 className="text-2xl font-bold text-slate-800">Detail User</h1></div></div><Link href={`/users/${user.id}/edit`} className="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700"><Pencil className="size-4" /> Edit</Link></div>
                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-4 bg-gradient-to-r from-cyan-600 to-cyan-500 p-6 text-white"><div className="flex size-16 items-center justify-center rounded-full bg-white/20"><UserRound className="size-8" /></div><div><h2 className="text-xl font-bold">{user.name}</h2><p className="text-sm text-cyan-100">@{user.username}</p></div></div>
                    <div className="grid gap-5 p-6 sm:grid-cols-2"><div><p className="text-xs uppercase tracking-wide text-slate-400">Username</p><p className="mt-1 font-medium text-slate-800">{user.username}</p></div><div><p className="text-xs uppercase tracking-wide text-slate-400">Jenis kelamin</p><p className="mt-1 font-medium text-slate-800">{user.jenis_kelamin || '-'}</p></div><div><p className="text-xs uppercase tracking-wide text-slate-400">Role</p><div className="mt-1 flex flex-wrap gap-2">{user.roles?.map((role) => <span key={role.id} className="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700"><ShieldCheck className="size-3" />{role.name}</span>)}</div></div><div><p className="text-xs uppercase tracking-wide text-slate-400">Dibuat</p><p className="mt-1 flex items-center gap-1 font-medium text-slate-800"><CalendarDays className="size-4 text-slate-400" />{new Date(user.created_at).toLocaleDateString('id-ID')}</p></div></div>
                </div>
            </section>
        </VillageDashboardLayout>
    );
}
