import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

// ─── Brand icons ─────────────────────────────────────────────────────────────

function LogoMark() {
    return (
        <svg width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true">
            <rect width="36" height="36" rx="8" fill="#7c3aed" fillOpacity="0.15" />
            <rect x="8" y="10" width="20" height="6" rx="2" stroke="#a78bfa" strokeWidth="1.5" fill="none" />
            <rect x="8" y="20" width="20" height="6" rx="2" stroke="#a78bfa" strokeWidth="1.5" fill="none" />
            <circle cx="12.5" cy="13" r="1.5" fill="#7c3aed" />
            <circle cx="12.5" cy="23" r="1.5" fill="#7c3aed" />
            <rect x="16" y="12" width="8" height="2" rx="1" fill="#a78bfa" fillOpacity="0.5" />
            <rect x="16" y="22" width="8" height="2" rx="1" fill="#a78bfa" fillOpacity="0.5" />
        </svg>
    );
}

function ServerIcon() {
    return (
        <svg className="h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <rect x="1" y="3" width="14" height="4" rx="1.5" stroke="currentColor" strokeWidth="1.25" />
            <rect x="1" y="9" width="14" height="4" rx="1.5" stroke="currentColor" strokeWidth="1.25" />
            <circle cx="3.5" cy="5" r="1" fill="currentColor" />
            <circle cx="3.5" cy="11" r="1" fill="currentColor" />
        </svg>
    );
}

function ShieldIcon() {
    return (
        <svg className="h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path
                d="M8 1.5L2 4v4c0 3.3 2.4 5.7 6 6.5C11.6 13.7 14 11.3 14 8V4L8 1.5z"
                stroke="currentColor"
                strokeWidth="1.25"
                strokeLinejoin="round"
            />
            <path d="M5.5 8l1.5 1.5L10.5 6" stroke="currentColor" strokeWidth="1.25" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function GlobeIcon() {
    return (
        <svg className="h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <circle cx="8" cy="8" r="6.5" stroke="currentColor" strokeWidth="1.25" />
            <path d="M8 1.5C8 1.5 5.5 4.5 5.5 8s2.5 6.5 2.5 6.5S10.5 11.5 10.5 8 8 1.5 8 1.5z" stroke="currentColor" strokeWidth="1.25" />
            <path d="M1.5 8h13M2 5h12M2 11h12" stroke="currentColor" strokeWidth="1.25" strokeLinecap="round" />
        </svg>
    );
}

function ClipboardIcon() {
    return (
        <svg className="h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <rect x="3" y="3" width="10" height="12" rx="1.5" stroke="currentColor" strokeWidth="1.25" />
            <path d="M6 2.5a.5.5 0 01.5-.5h3a.5.5 0 01.5.5V3.5a.5.5 0 01-.5.5h-3A.5.5 0 016 3.5V2.5z" stroke="currentColor" strokeWidth="1.25" />
            <path d="M5.5 8h5M5.5 10.5h3" stroke="currentColor" strokeWidth="1.25" strokeLinecap="round" />
        </svg>
    );
}

// ─── Feature list ─────────────────────────────────────────────────────────────

const FEATURES = [
    {
        Icon: ServerIcon,
        title: 'VPS lifecycle',
        desc: 'Provision, resize, restart, and decommission servers on demand',
    },
    {
        Icon: ShieldIcon,
        title: 'Firewall & access control',
        desc: 'Granular inbound/outbound rules with per-engineer time-bound grants',
    },
    {
        Icon: GlobeIcon,
        title: 'DNS management',
        desc: 'A, CNAME, MX, and TXT records across all your domains in one place',
    },
    {
        Icon: ClipboardIcon,
        title: 'Governance & audit',
        desc: 'Access reviews, approval flows, and tamper-proof audit log exports',
    },
] as const;

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/login');
    }

    const genericError =
        errors.email === undefined && errors.password === undefined && Object.keys(errors).length > 0 ? (Object.values(errors)[0] as string) : null;

    return (
        <>
            <Head title="Sign in" />

            <div className="flex min-h-screen">
                {/* ── Left branding panel ── */}
                <div
                    className="relative hidden flex-col justify-between overflow-hidden bg-slate-950 dot-grid px-12 py-10 lg:flex lg:w-3/5"
                    style={{
                        backgroundImage: `
                            radial-gradient(ellipse 60% 50% at 20% 110%, #7c3aed22 0%, transparent 70%),
                            radial-gradient(ellipse 40% 40% at 85% -10%, #7c3aed18 0%, transparent 60%),
                            url("data:image/svg+xml,%3Csvg width='24' height='24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='1' cy='1' r='1' fill='%23ffffff' fill-opacity='0.05'/%3E%3C/svg%3E")
                        `,
                    }}
                >
                    {/* Logo */}
                    <div className="flex items-center gap-3">
                        <LogoMark />
                        <div>
                            <p className="text-base font-semibold tracking-tight text-white">Hostinger VPS</p>
                            <p className="font-mono text-xs text-slate-500">engineer console</p>
                        </div>
                    </div>

                    {/* Hero */}
                    <div className="space-y-8">
                        <div className="space-y-4">
                            <div className="inline-flex items-center gap-2 rounded-full border border-brand-800 bg-brand-950 px-3 py-1">
                                <span className="h-1.5 w-1.5 rounded-full bg-brand-400" />
                                <span className="font-mono text-xs text-brand-400">production-ready</span>
                            </div>

                            <h1 className="text-4xl leading-tight font-semibold tracking-tight text-white">
                                Engineer-grade
                                <br />
                                <span className="bg-gradient-to-r from-brand-400 to-violet-300 bg-clip-text text-transparent">
                                    infrastructure
                                    <br />
                                    management
                                </span>
                            </h1>

                            <p className="max-w-sm text-base leading-relaxed text-slate-400">
                                One console for your entire Hostinger fleet — servers, DNS, firewall, and access governance, all wired together.
                            </p>
                        </div>

                        {/* Features */}
                        <ul className="space-y-4">
                            {FEATURES.map(({ Icon, title, desc }) => (
                                <li key={title} className="flex items-start gap-3">
                                    <span className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-brand-800 bg-brand-950 text-brand-400">
                                        <Icon />
                                    </span>
                                    <div>
                                        <p className="text-sm font-medium text-slate-200">{title}</p>
                                        <p className="mt-0.5 text-sm text-slate-500">{desc}</p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Footer */}
                    <p className="font-mono text-xs text-slate-600">&copy; {new Date().getFullYear()} Novos Horizontes</p>
                </div>

                {/* ── Right form panel ── */}
                <div className="flex flex-1 flex-col items-center justify-center bg-white px-6 py-12 lg:px-16">
                    {/* Mobile logo */}
                    <div className="mb-10 flex items-center gap-3 lg:hidden">
                        <LogoMark />
                        <div>
                            <p className="text-base font-semibold tracking-tight text-slate-900">Hostinger VPS</p>
                            <p className="font-mono text-xs text-slate-400">engineer console</p>
                        </div>
                    </div>

                    <div className="w-full max-w-sm">
                        <div className="mb-8 space-y-1">
                            <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Welcome back</h2>
                            <p className="text-sm text-slate-500">Sign in to access your infrastructure</p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-5" noValidate>
                            <div className="space-y-1.5">
                                <Label htmlFor="email" className="text-sm font-medium text-slate-700">
                                    Email address
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="email"
                                    autoFocus
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="you@company.com"
                                    className={errors.email ? 'border-red-400 focus-visible:ring-red-400' : ''}
                                />
                                {errors.email && <p className="text-xs text-red-600">{errors.email}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <div className="flex items-center justify-between gap-3">
                                    <Label htmlFor="password" className="text-sm font-medium text-slate-700">
                                        Password
                                    </Label>
                                    <Link href="/forgot-password" className="text-xs font-medium text-brand-700 hover:text-brand-900">
                                        Forgot password?
                                    </Link>
                                </div>
                                <Input
                                    id="password"
                                    type="password"
                                    autoComplete="current-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className={errors.password ? 'border-red-400 focus-visible:ring-red-400' : ''}
                                />
                                {errors.password && <p className="text-xs text-red-600">{errors.password}</p>}
                            </div>

                            {genericError && <p className="rounded-md bg-red-50 px-3 py-2 text-xs text-red-600">{genericError}</p>}

                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex h-10 w-full items-center justify-center rounded-md bg-brand-600 px-4 text-sm font-medium text-white transition-colors hover:bg-brand-700 focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                            >
                                {processing ? (
                                    <>
                                        <svg className="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        Signing in…
                                    </>
                                ) : (
                                    'Sign in'
                                )}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
