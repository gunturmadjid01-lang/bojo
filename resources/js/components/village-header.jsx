import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useInitials } from '@/hooks/use-initials';
import { usePage, Link } from '@inertiajs/react';
import { ChevronDown, LogOut, Menu, PanelLeftClose, PanelLeftOpen, Settings, UsersRound } from 'lucide-react';

export default function VillageHeader({ userName = 'Sekdes', sidebarOpen, onToggleSidebar }) {
    const { auth } = usePage().props;
    const user = auth?.user ?? { name: userName };
    const getInitials = useInitials();
    const avatarSrc = user.avatar ? (user.avatar.startsWith('http') ? user.avatar : `/storage/${user.avatar}`) : '';

    return (
        <header className="fixed inset-x-0 top-0 z-50 flex h-[51px] items-center bg-[#08b4d4] text-white shadow-sm">
            <div className="flex h-full w-[226px] items-center gap-2 bg-[#0ca4c2] px-4 text-lg font-semibold">
                <UsersRound className="size-5 text-yellow-300" aria-hidden="true" />
                <span>DESABOJO</span>
            </div>
            <button type="button" onClick={onToggleSidebar} className="flex h-full w-14 items-center justify-center hover:bg-white/10" aria-label={sidebarOpen ? 'Sembunyikan sidebar' : 'Tampilkan sidebar'}>
                <span className="hidden lg:block">
                    {sidebarOpen ? <PanelLeftClose className="size-5" /> : <PanelLeftOpen className="size-5" />}
                </span>
                <Menu className="size-5 lg:hidden" />
            </button>

            <div className="ml-auto flex items-center px-4">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button type="button" className="flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-2 py-1 text-left text-sm text-white transition hover:bg-white/15">
                            <Avatar className="size-8 border border-white/40 bg-white/10">
                                <AvatarImage src={avatarSrc} alt={user.name || 'User'} />
                                <AvatarFallback className="bg-white/20 text-xs text-white">
                                    {getInitials(user.name || userName)}
                                </AvatarFallback>
                            </Avatar>
                            <span className="hidden sm:inline">{user.name || userName}</span>
                            <ChevronDown className="size-4 opacity-80" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-64">
                        <div className="px-2 py-2">
                            <UserInfo user={{ ...user, avatar: avatarSrc }} showEmail={true} />
                        </div>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href={route('profile.edit')} className="flex w-full items-center">
                                <Settings className="mr-2 size-4" />
                                Update Profile
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link method="post" href={route('logout')} as="button" className="flex w-full items-center">
                                <LogOut className="mr-2 size-4" />
                                Logout
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
