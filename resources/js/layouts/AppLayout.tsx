import { Alert } from '@/components/ui/Alert';
import { usePermission } from '@/hooks/usePermission';
import { SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { ReactNode, useEffect, useState } from 'react';

interface NavItem {
    label: string;
    href: string;
    match?: string;
}

interface NavGroup {
    title: string;
    items: NavItem[];
    rootOnly?: boolean;
}

const NAV: NavGroup[] = [
    {
        title: 'Infrastructure',
        items: [
            { label: 'Dashboard',  href: '/' },
            { label: 'VPS',        href: '/vps',    match: '/vps' },
        ],
    },
    {
        title: 'Services',
        items: [
            { label: 'Domains',    href: '/domains',  match: '/domains' },
            { label: 'DNS',        href: '/dns/example.com', match: '/dns' },
            { label: 'Billing',    href: '/billing',  match: '/billing' },
        ],
    },
    {
        title: 'Governance',
        items: [
            { label: 'Access Reviews', href: '/governance/reviews',   match: '/governance/reviews' },
            { label: 'Audit Export',   href: '/governance/audit',     match: '/governance/audit' },
            { label: 'Approvals',      href: '/governance/approvals', match: '/governance/approvals' },
        ],
    },
    {
        title: 'Operations',
        rootOnly: true,
        items: [
            { label: 'Health',   href: '/ops/health' },
            { label: 'Quota',    href: '/ops/quota' },
            { label: 'Cache',    href: '/ops/cache' },
            { label: 'Database', href: '/ops/database' },
        ],
    },
];

function NavLink({ href, label, current }: { href: string; label: string; current: boolean }) {
    return (
        <Link
            href={href}
            className={`block rounded-md px-3 py-2 text-sm transition-colors ${
                current
                    ? 'bg-gray-900 text-white font-medium'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
            }`}
        >
            {label}
        </Link>
    );
}

interface AppLayoutProps {
    children: ReactNode;
    title?: string;
}

export default function AppLayout({ children, title }: AppLayoutProps) {
    const { auth, flash } = usePage<SharedData>().props;
    const { isRoot } = usePermission();
    const url = usePage().url;

    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [flashMsg, setFlashMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    useEffect(() => {
        if (flash.success) setFlashMsg({ type: 'success', text: flash.success });
        else if (flash.error) setFlashMsg({ type: 'error', text: flash.error });
        else setFlashMsg(null);

        const t = setTimeout(() => setFlashMsg(null), 4000);
        return () => clearTimeout(t);
    }, [flash]);

    function handleLogout() {
        router.post('/logout');
    }

    return (
        <div className="flex min-h-screen bg-gray-50">
            {/* Sidebar */}
            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-white border-r border-gray-200 transform transition-transform duration-200 lg:static lg:translate-x-0 ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                {/* Logo */}
                <div className="flex h-16 items-center border-b border-gray-200 px-6">
                    <Link href="/" className="text-lg font-bold text-gray-900">
                        Hostinger
                    </Link>
                </div>

                {/* Nav */}
                <nav className="flex-1 overflow-y-auto p-4 space-y-6">
                    {NAV.filter((g) => !g.rootOnly || isRoot()).map((group) => (
                        <div key={group.title}>
                            <p className="mb-1 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                {group.title}
                            </p>
                            <div className="space-y-0.5">
                                {group.items.map((item) => (
                                    <NavLink
                                        key={item.href}
                                        href={item.href}
                                        label={item.label}
                                        current={item.match ? url.startsWith(item.match) : url === item.href}
                                    />
                                ))}
                            </div>
                        </div>
                    ))}
                </nav>

                {/* User */}
                <div className="border-t border-gray-200 p-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-white text-xs font-bold">
                            {auth.user.name.charAt(0).toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium text-gray-900">{auth.user.name}</p>
                            <p className="truncate text-xs text-gray-500">{auth.user.email}</p>
                        </div>
                        <button onClick={handleLogout} className="text-xs text-gray-400 hover:text-gray-700">
                            Logout
                        </button>
                    </div>
                </div>
            </aside>

            {/* Overlay for mobile */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-30 bg-black/50 lg:hidden" onClick={() => setSidebarOpen(false)} />
            )}

            {/* Main */}
            <div className="flex flex-1 flex-col min-w-0">
                {/* Topbar */}
                <header className="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-6">
                    <button
                        className="lg:hidden text-gray-500 hover:text-gray-700"
                        onClick={() => setSidebarOpen(!sidebarOpen)}
                    >
                        <span className="sr-only">Toggle sidebar</span>
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {title && <h1 className="text-base font-semibold text-gray-900">{title}</h1>}
                </header>

                {/* Flash */}
                {flashMsg && (
                    <div className="px-6 pt-4">
                        <Alert variant={flashMsg.type === 'success' ? 'success' : 'destructive'}>
                            {flashMsg.text}
                        </Alert>
                    </div>
                )}

                {/* Content */}
                <main className="flex-1 p-6">{children}</main>
            </div>
        </div>
    );
}
