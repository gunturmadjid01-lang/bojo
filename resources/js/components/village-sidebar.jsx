import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    BriefcaseBusiness,
    ChevronDown,
    ChevronRight,
    Database,
    Gauge,
    LogOut,
    MapPinned,
    Settings,
    ShieldCheck,
    UserCog,
    UsersRound,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { canAccessAnyActionHref, canAccessHref, hasPermissionName } from '@/lib/permissions';

const masterMenuItems = [
    { label: 'Master Dusun', href: '/dusun', icon: MapPinned },
    { label: 'Master Pendidikan', href: '/pendidikan', icon: BookOpen },
    { label: 'Master Pekerjaan', href: '/pekerjaan', icon: BriefcaseBusiness },
];

const kependudukanMenuItems = [
    { label: 'Kartu Keluarga', href: '/kartu-keluarga', icon: Database },
    { label: 'Anggota Keluarga', href: '/anggota-kartu-keluarga', icon: UsersRound },
    { label: 'Penduduk', href: '/penduduk', icon: UsersRound },
    { label: 'Kelahiran', href: '/kelahiran', icon: UsersRound },
    { label: 'Kematian', href: '/kematian', icon: UsersRound },
    { label: 'Penduduk Masuk', href: '/penduduk-datang', icon: UsersRound },
    { label: 'Penduduk Keluar', href: '/penduduk-pindah', icon: UsersRound },
];

const systemSettingsMenuItems = [
    { label: 'Manajemen User', href: '/users', icon: UserCog },
    { label: 'Matrix Permission', href: '/permissions', icon: ShieldCheck },
];

const perangkatDesaMenuItems = [
    { label: 'Master Jabatan Desa', href: '/jabatan-desa', icon: ShieldCheck },
    { label: 'Penugasan Jabatan', href: '/penugasan-perangkat', icon: UserCog, permission: 'lihat-penugasan-perangkat' },
    { label: 'Riwayat Penugasan', href: '/penugasan-perangkat/riwayat', icon: Database, permission: 'lihat-riwayat-penugasan' },
];
export default function VillageSidebar({ open = true, onClose }) {
    const page = usePage();
    const { url } = page;
    const { desa, auth } = page.props;
    const user = auth?.user;
    const villageName = desa?.nama_desa || 'Desa Bojo';
    const logoUrl = desa?.logo ? `/${desa.logo}` : '/logo-desa-bojo.svg';

    const visibleMenu = useMemo(() => {
        return {
            desa: canAccessAnyActionHref(user, '/desa'),
            master: masterMenuItems.filter((item) => canAccessAnyActionHref(user, item.href)),
            kependudukan: kependudukanMenuItems.filter((item) => canAccessAnyActionHref(user, item.href)),
            system: systemSettingsMenuItems.filter((item) => canAccessAnyActionHref(user, item.href)),
            perangkat: perangkatDesaMenuItems.filter((item) => canAccessAnyActionHref(user, item.href) || (item.permission && hasPermissionName(user, item.permission))),
            reports: canAccessAnyActionHref(user, '/reports'),
        };
    }, [user]);

    const isMasterRoute = visibleMenu.master.some((item) => url === item.href || url.startsWith(item.href));
    const isKependudukanRoute = visibleMenu.kependudukan.some((item) => url === item.href || url.startsWith(item.href));
    const isSystemSettingsRoute = visibleMenu.system.some((item) => url === item.href || url.startsWith(item.href));
    const isPerangkatDesaRoute = visibleMenu.perangkat.some((item) => url === item.href || url.startsWith(item.href));

    const firstVisibleGroup = useMemo(() => {
        const order = ['perangkat-desa', 'master-data', 'kependudukan', 'system-settings'];
        return order.find((groupId) => {
            const groupMap = {
                'perangkat-desa': visibleMenu.perangkat,
                'master-data': visibleMenu.master,
                kependudukan: visibleMenu.kependudukan,
                'system-settings': visibleMenu.system,
            };
            return (groupMap[groupId] ?? []).length > 0;
        }) || '';
    }, [visibleMenu]);

    const [openGroup, setOpenGroup] = useState(
        isMasterRoute
            ? 'master-data'
            : isKependudukanRoute
              ? 'kependudukan'
              : isSystemSettingsRoute
                  ? 'system-settings'
                  : isPerangkatDesaRoute
                    ? 'perangkat-desa'
                    : firstVisibleGroup,
    );

    useEffect(() => {
        if (!openGroup && firstVisibleGroup) {
            setOpenGroup(firstVisibleGroup);
        }
    }, [firstVisibleGroup, openGroup]);

    const toggleGroup = (groupId) => {
        setOpenGroup((current) => (current === groupId ? '' : groupId));
    };

    const isActive = (href) => url === href || (href !== '/dashboard' && url.startsWith(href));

    return (
        <>
            {open && <button type="button" onClick={onClose} className="fixed inset-0 z-30 bg-black/30 lg:hidden" aria-label="Tutup menu" />}
            <aside
                className={`fixed top-[51px] bottom-0 left-0 z-40 w-[356px] border-r border-slate-200 bg-white transition-transform duration-200 ${open ? 'translate-x-0 lg:translate-x-0' : '-translate-x-full lg:-translate-x-full'}`}
            >
                <nav className="flex h-full flex-col pt-1" aria-label="Menu utama">
                    <Link
                        href="/dashboard"
                        onClick={onClose}
                        className="flex items-center gap-2 border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-700"
                    >
                        <img src={logoUrl} alt={`Logo ${villageName}`} className="size-8 rounded-full object-contain" />
                        <span className="truncate">{villageName}</span>
                    </Link>

                    <div className="space-y-1 px-2 py-2">
                        <Link
                            href="/dashboard"
                            onClick={onClose}
                            className={`flex h-[44px] items-center gap-3 rounded-md px-3 text-sm transition-colors ${isActive('/dashboard') ? 'bg-[#08a8c8] text-white' : 'text-[#009ac1] hover:bg-cyan-50'}`}
                        >
                            <Gauge className="size-4" aria-hidden="true" />
                            <span>Dashboard</span>
                        </Link>

                        {visibleMenu.desa && (
                            <Link
                                href="/desa"
                                onClick={onClose}
                                className={`flex h-[44px] items-center gap-3 rounded-md px-3 text-sm transition-colors ${isActive('/desa') ? 'bg-[#08a8c8] text-white' : 'text-[#009ac1] hover:bg-cyan-50'}`}
                            >
                                <Settings className="size-4" aria-hidden="true" />
                                <span>Setting Desa</span>
                            </Link>
                        )}
                        {visibleMenu.reports && <Link href="/laporan" onClick={onClose} className={`flex h-[44px] items-center gap-3 rounded-md px-3 text-sm transition-colors ${isActive('/laporan') ? 'bg-[#08a8c8] text-white' : 'text-[#009ac1] hover:bg-cyan-50'}`}><Gauge className="size-4" aria-hidden="true" /><span>Laporan & Statistik</span></Link>}
                        {visibleMenu.perangkat.length > 0 && <div className="overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                            <button
                                type="button"
                                onClick={() => toggleGroup('perangkat-desa')}
                                className={`flex w-full items-center justify-between gap-3 px-3 py-3 text-left text-sm font-medium transition-colors ${isPerangkatDesaRoute ? 'bg-cyan-50 text-cyan-700' : 'text-[#009ac1] hover:bg-cyan-50'}`}
                            >
                                <span className="flex items-center gap-3">
                                    <UserCog className="size-4" aria-hidden="true" />
                                    <span>Perangkat Desa</span>
                                </span>
                                {openGroup === 'perangkat-desa' ? <ChevronDown className="size-4" /> : <ChevronRight className="size-4" />}
                            </button>
                            {openGroup === 'perangkat-desa' && (
                                <div className="border-t border-slate-200 bg-white">
                                    {visibleMenu.perangkat.map(({ label, href, icon: Icon }) => (
                                        <Link
                                            key={href}
                                            href={href}
                                            onClick={onClose}
                                            className={`flex h-[40px] items-center gap-3 px-5 text-sm transition-colors ${isActive(href) ? 'bg-[#08a8c8] text-white' : 'text-[#009ac1] hover:bg-cyan-50'}`}
                                        >
                                            <Icon className="size-4" aria-hidden="true" />
                                            <span>{label}</span>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </div>}
                        {/* master data */}
                        {visibleMenu.master.length > 0 && <div className="overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                            <button
                                type="button"
                                onClick={() => toggleGroup('master-data')}
                                className={`flex w-full items-center justify-between gap-3 px-3 py-3 text-left text-sm font-medium transition-colors ${isMasterRoute ? 'bg-cyan-50 text-cyan-700' : 'text-[#009ac1] hover:bg-cyan-50'}`}
                            >
                                <span className="flex items-center gap-3">
                                    <Database className="size-4" aria-hidden="true" />
                                    <span>Master Data</span>
                                </span>
                                {openGroup === 'master-data' ? <ChevronDown className="size-4" /> : <ChevronRight className="size-4" />}
                            </button>

                            {openGroup === 'master-data' && (
                                <div className="border-t border-slate-200 bg-white">
                                    {visibleMenu.master.map(({ label, href, icon: Icon }) => {
                                        const active = isActive(href);
                                        return (
                                            <Link
                                                key={href}
                                                href={href}
                                                onClick={onClose}
                                                className={`flex h-[40px] items-center gap-3 px-5 text-sm transition-colors ${active ? 'bg-[#08a8c8] text-white' : 'text-[#009ac1] hover:bg-cyan-50'}`}
                                            >
                                                <Icon className="size-4" aria-hidden="true" />
                                                <span>{label}</span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            )}
                        </div>}
                        {/* kependudukan */}
                        {visibleMenu.kependudukan.length > 0 && <div className="overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                            <button
                                type="button"
                                onClick={() => toggleGroup('kependudukan')}
                                className={`flex w-full items-center justify-between gap-3 px-3 py-3 text-left text-sm font-medium transition-colors ${isKependudukanRoute ? 'bg-cyan-50 text-cyan-700' : 'text-[#009ac1] hover:bg-cyan-50'}`}
                            >
                                <span className="flex items-center gap-3">
                                    <UsersRound className="size-4" aria-hidden="true" />
                                    <span>Kependudukan</span>
                                </span>
                                {openGroup === 'kependudukan' ? <ChevronDown className="size-4" /> : <ChevronRight className="size-4" />}
                            </button>
                            {openGroup === 'kependudukan' && (
                                <div className="border-t border-slate-200 bg-white">
                                    {visibleMenu.kependudukan.map(({ label, href, icon: Icon }) => (
                                        <Link
                                            key={href}
                                            href={href}
                                            onClick={onClose}
                                            className={`flex h-[40px] items-center gap-3 px-5 text-sm transition-colors ${isActive(href) ? 'bg-[#08a8c8] text-white' : 'text-[#009ac1] hover:bg-cyan-50'}`}
                                        >
                                            <Icon className="size-4" aria-hidden="true" />
                                            <span>{label}</span>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </div>}

                        {visibleMenu.system.length > 0 && <div className="overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                            <button
                                type="button"
                                onClick={() => toggleGroup('system-settings')}
                                className={`flex w-full items-center justify-between gap-3 px-3 py-3 text-left text-sm font-medium transition-colors ${isSystemSettingsRoute ? 'bg-cyan-50 text-cyan-700' : 'text-[#009ac1] hover:bg-cyan-50'}`}
                            >
                                <span className="flex items-center gap-3">
                                    <Settings className="size-4" aria-hidden="true" />
                                    <span>Pengaturan Sistem</span>
                                </span>
                                {openGroup === 'system-settings' ? <ChevronDown className="size-4" /> : <ChevronRight className="size-4" />}
                            </button>

                            {openGroup === 'system-settings' && (
                                <div className="border-t border-slate-200 bg-white">
                                    {visibleMenu.system.map(({ label, href, icon: Icon }) => {
                                        const active = isActive(href);
                                        return (
                                            <Link
                                                key={href}
                                                href={href}
                                                onClick={onClose}
                                                className={`flex h-[40px] items-center gap-3 px-5 text-sm transition-colors ${active ? 'bg-[#08a8c8] text-white' : 'text-[#009ac1] hover:bg-cyan-50'}`}
                                            >
                                                <Icon className="size-4" aria-hidden="true" />
                                                <span>{label}</span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            )}
                        </div>}
                    </div>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="mt-auto flex items-center gap-3 px-4 py-3 text-left text-sm text-[#009ac1] hover:bg-cyan-50"
                    >
                        <LogOut className="size-4" aria-hidden="true" />
                        <span>Logout</span>
                    </Link>
                </nav>
            </aside>
        </>
    );
}
