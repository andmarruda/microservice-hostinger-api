import { ReactNode } from 'react';
import { vi } from 'vitest';

// ─── Shared defaults ─────────────────────────────────────────────────────────

export const defaultSharedData = {
    name: 'Hostinger',
    auth: {
        user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            email_verified_at: null as string | null,
            created_at: '2024-01-01T00:00:00.000000Z',
            updated_at: '2024-01-01T00:00:00.000000Z',
        },
        roles: [] as string[],
        permissions: [] as string[],
    },
    flash: { success: null as string | null, error: null as string | null },
};

// ─── Inertia exports ─────────────────────────────────────────────────────────

export const usePage = vi.fn(() => ({
    props: { ...defaultSharedData },
    url: '/',
    component: '',
    version: null,
}));

export function Link({
    href,
    children,
    className,
    ...props
}: {
    href: string;
    children?: ReactNode;
    className?: string;
    [key: string]: unknown;
}) {
    return (
        <a href={href} className={className} {...(props as object)}>
            {children}
        </a>
    );
}

export function Head({ title }: { title?: string }) {
    return <title data-testid="head-title">{title ?? ''}</title>;
}

export const useForm = vi.fn((initial: Record<string, unknown> = {}) => ({
    data: { ...initial },
    setData: vi.fn((key: string, value: unknown) => {
        /* captured by vi.fn */
    }),
    post: vi.fn(),
    get: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    errors: {} as Record<string, string>,
    processing: false,
    reset: vi.fn(),
    clearErrors: vi.fn(),
    transform: vi.fn(),
    wasSuccessful: false,
    recentlySuccessful: false,
    isDirty: false,
}));

export const router = {
    post: vi.fn(),
    get: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    visit: vi.fn(),
    reload: vi.fn(),
};

export const createInertiaApp = vi.fn();
export const InertiaApp = vi.fn();
