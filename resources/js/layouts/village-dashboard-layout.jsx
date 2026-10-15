import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import VillageHeader from '@/components/village-header';
import VillageSidebar from '@/components/village-sidebar';

export default function VillageDashboardLayout({ children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { flash = {} } = usePage().props;

    return (
        <div className="min-h-screen bg-[#f7f7f7] text-slate-800">
            <VillageHeader sidebarOpen={sidebarOpen} onToggleSidebar={() => setSidebarOpen((open) => !open)} />
            <VillageSidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />
            <main className={`min-h-screen pt-[51px] transition-[padding] duration-200 ${sidebarOpen ? 'lg:pl-[356px]' : 'lg:pl-0'}`}>
                <div className="min-h-[calc(100vh-51px)] border-t-2 border-cyan-500 bg-[#f7f7f7] p-3 sm:p-4 lg:p-6">
                    {(flash.success || flash.error) && (
                        <div className={`mb-4 rounded-md border px-4 py-3 text-sm ${flash.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'}`} role="alert">
                            {flash.success || flash.error}
                        </div>
                    )}
                    {children}
                </div>
            </main>
        </div>
    );
}
