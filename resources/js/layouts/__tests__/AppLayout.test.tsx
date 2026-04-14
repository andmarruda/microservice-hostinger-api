import { usePage } from '@inertiajs/react';
import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import AppLayout from '../AppLayout';

function mockPageData(overrides: {
    roles?: string[];
    success?: string | null;
    error?: string | null;
} = {}) {
    vi.mocked(usePage).mockReturnValue({
        props: {
            name: 'Hostinger',
            auth: {
                user: { id: 1, name: 'Alice', email: 'alice@example.com', email_verified_at: null, created_at: '', updated_at: '' },
                roles: overrides.roles ?? [],
                permissions: [],
            },
            flash: {
                success: overrides.success ?? null,
                error: overrides.error ?? null,
            },
        },
        url: '/vps',
        component: '',
        version: null,
    } as ReturnType<typeof usePage>);
}

describe('AppLayout', () => {
    it('renders children', () => {
        mockPageData();
        render(<AppLayout><p>Page content</p></AppLayout>);
        expect(screen.getByText('Page content')).toBeInTheDocument();
    });

    it('renders page title in topbar', () => {
        mockPageData();
        render(<AppLayout title="My Page"><p>x</p></AppLayout>);
        expect(screen.getByText('My Page')).toBeInTheDocument();
    });

    it('renders user name and email in sidebar', () => {
        mockPageData();
        render(<AppLayout><p>x</p></AppLayout>);
        expect(screen.getByText('Alice')).toBeInTheDocument();
        expect(screen.getByText('alice@example.com')).toBeInTheDocument();
    });

    it('renders Hostinger brand link', () => {
        mockPageData();
        render(<AppLayout><p>x</p></AppLayout>);
        expect(screen.getByText('Hostinger')).toBeInTheDocument();
    });

    it('shows Infrastructure and Services nav groups for regular users', () => {
        mockPageData({ roles: [] });
        render(<AppLayout><p>x</p></AppLayout>);
        expect(screen.getByText('Infrastructure')).toBeInTheDocument();
        expect(screen.getByText('Services')).toBeInTheDocument();
    });

    it('hides Operations nav group for non-root users', () => {
        mockPageData({ roles: [] });
        render(<AppLayout><p>x</p></AppLayout>);
        expect(screen.queryByText('Operations')).not.toBeInTheDocument();
    });

    it('shows Operations nav group for root users', () => {
        mockPageData({ roles: ['root'] });
        render(<AppLayout><p>x</p></AppLayout>);
        expect(screen.getByText('Operations')).toBeInTheDocument();
    });

    it('shows success flash message', () => {
        mockPageData({ success: 'Action completed!' });
        render(<AppLayout><p>x</p></AppLayout>);
        expect(screen.getByText('Action completed!')).toBeInTheDocument();
    });

    it('shows error flash message', () => {
        mockPageData({ error: 'Something went wrong' });
        render(<AppLayout><p>x</p></AppLayout>);
        expect(screen.getByText('Something went wrong')).toBeInTheDocument();
    });

    it('auto-dismisses flash after 4 seconds', async () => {
        vi.useFakeTimers();
        mockPageData({ success: 'Done!' });
        render(<AppLayout><p>x</p></AppLayout>);
        expect(screen.getByText('Done!')).toBeInTheDocument();
        await act(async () => { vi.advanceTimersByTime(4100); });
        expect(screen.queryByText('Done!')).not.toBeInTheDocument();
        vi.useRealTimers();
    });

    it('shows mobile sidebar toggle button', () => {
        mockPageData();
        render(<AppLayout><p>x</p></AppLayout>);
        expect(screen.getByRole('button', { name: /toggle sidebar/i })).toBeInTheDocument();
    });

    it('opens sidebar on mobile toggle click', async () => {
        mockPageData();
        render(<AppLayout><p>x</p></AppLayout>);
        const toggle = screen.getByRole('button', { name: /toggle sidebar/i });
        await userEvent.click(toggle);
        // overlay should appear
        expect(document.querySelector('.bg-black\\/50')).toBeInTheDocument();
    });

    it('closes sidebar when overlay is clicked', async () => {
        mockPageData();
        render(<AppLayout><p>x</p></AppLayout>);
        await userEvent.click(screen.getByRole('button', { name: /toggle sidebar/i }));
        const overlay = document.querySelector('.bg-black\\/50') as HTMLElement;
        await userEvent.click(overlay);
        expect(document.querySelector('.bg-black\\/50')).not.toBeInTheDocument();
    });

    it('logout button calls router.post', async () => {
        const { router } = await import('@inertiajs/react');
        mockPageData();
        render(<AppLayout><p>x</p></AppLayout>);
        await userEvent.click(screen.getByRole('button', { name: /logout/i }));
        expect(router.post).toHaveBeenCalledWith('/logout');
    });
});
