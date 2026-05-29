import { usePage } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import Dashboard from '../Dashboard';

const defaultProps = {
    vpsCount: 5,
    openReviews: 2,
    pendingApprovals: 1,
    openDriftReports: 3,
    queuePending: 10,
    queueFailed: 0,
    quota: {
        total: 40,
        warn_at: 100,
        hard_limit: 120,
        by_resource: { vps: 15, dns: 25 },
        percent: 40,
        status: 'ok' as const,
    },
};

function mockPageData(overrides: { roles?: string[]; permissions?: string[] } = {}) {
    vi.mocked(usePage).mockReturnValue({
        props: {
            name: 'Hostinger',
            auth: {
                user: {
                    id: 1,
                    name: 'Alice',
                    email: 'alice@example.com',
                    email_verified_at: null,
                    created_at: '',
                    updated_at: '',
                },
                roles: overrides.roles ?? [],
                permissions: overrides.permissions ?? [],
            },
            flash: { success: null, error: null },
        },
        url: '/dashboard',
        component: '',
        version: null,
    } as unknown as ReturnType<typeof usePage>);
}

describe('Dashboard page', () => {
    it('renders VPS count for users with VPS read permission', () => {
        mockPageData({ permissions: ['VPS.VirtualMachine.Manage.read'] });
        render(<Dashboard {...defaultProps} />);
        expect(screen.getByText('5')).toBeInTheDocument();
        expect(screen.getAllByText('VPS').length).toBeGreaterThan(0);
    });

    it('renders enabled domain shortcut when domain permission is present', () => {
        mockPageData({ permissions: ['Domains.Availability.validate'] });
        render(<Dashboard {...defaultProps} />);
        expect(screen.getAllByText('Domains').length).toBeGreaterThan(0);
        expect(screen.queryByText('VPS Instances')).not.toBeInTheDocument();
    });

    it('renders root-only governance and operations metrics for root', () => {
        mockPageData({ roles: ['root'], permissions: ['VPS.VirtualMachine.Manage.read'] });
        render(<Dashboard {...defaultProps} />);
        expect(screen.getByText('Open Drift Reports')).toBeInTheDocument();
        expect(screen.getByText('Pending Approvals')).toBeInTheDocument();
        expect(screen.getByText('Open Access Reviews')).toBeInTheDocument();
        expect(screen.getAllByText('Governance').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Operations').length).toBeGreaterThan(0);
    });

    it('hides root-only metrics for non-root users', () => {
        mockPageData({ permissions: ['VPS.VirtualMachine.Manage.read'] });
        render(<Dashboard {...defaultProps} />);
        expect(screen.queryByText('Open Drift Reports')).not.toBeInTheDocument();
        expect(screen.queryByText('Resource Quota')).not.toBeInTheDocument();
        expect(screen.queryByText('Queue Health')).not.toBeInTheDocument();
    });

    it('renders quota percentage for root users', () => {
        mockPageData({ roles: ['root'] });
        render(<Dashboard {...defaultProps} />);
        expect(screen.getByText('40%')).toBeInTheDocument();
    });

    it('renders quota bar green when status is ok', () => {
        mockPageData({ roles: ['root'] });
        const { container } = render(<Dashboard {...defaultProps} />);
        expect(container.querySelector('.bg-green-500')).toBeInTheDocument();
    });

    it('renders quota bar yellow when status is warning', () => {
        mockPageData({ roles: ['root'] });
        const { container } = render(<Dashboard {...defaultProps} quota={{ ...defaultProps.quota, percent: 75, status: 'warning' }} />);
        expect(container.querySelector('.bg-yellow-500')).toBeInTheDocument();
    });

    it('renders quota bar red when status is exceeded', () => {
        mockPageData({ roles: ['root'] });
        const { container } = render(<Dashboard {...defaultProps} quota={{ ...defaultProps.quota, percent: 100, status: 'exceeded' }} />);
        expect(container.querySelector('.bg-red-500')).toBeInTheDocument();
    });

    it('renders failed jobs in red when root has failed queue jobs', () => {
        mockPageData({ roles: ['root'] });
        render(<Dashboard {...defaultProps} queueFailed={3} />);
        const els = screen.getAllByText('3');
        const redEl = els.find((el) => el.classList.contains('text-red-700'));
        expect(redEl).toBeTruthy();
    });

    it('shows an empty workspace state when no permissions are enabled', () => {
        mockPageData();
        render(<Dashboard {...defaultProps} />);
        expect(screen.getByText(/no infrastructure workspace is enabled/i)).toBeInTheDocument();
    });
});
