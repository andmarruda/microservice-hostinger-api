import { router, usePage } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import ApprovalsIndex from '../Index';

function setCurrentUser(id: number) {
    vi.mocked(usePage).mockReturnValue({
        props: {
            name: 'Hostinger',
            auth: {
                user: { id, name: 'Root', email: 'root@example.com', email_verified_at: null, created_at: '', updated_at: '' },
                roles: ['root'],
                permissions: [],
            },
            flash: { success: null, error: null },
        },
        url: '/',
        component: '',
        version: null,
    } as ReturnType<typeof usePage>);
}

const approvals = [
    {
        id: 'ap-1',
        requester_id: '2',
        permission: 'vps.write',
        reason: 'Need to restart VPS',
        status: 'pending' as const,
        created_at: '2024-01-01',
    },
    {
        id: 'ap-2',
        requester_id: '3',
        permission: 'dns.write',
        reason: null,
        status: 'approved' as const,
        created_at: '2024-01-02',
    },
    {
        id: 'ap-3',
        requester_id: '1',
        permission: 'billing.read',
        reason: 'Own request',
        status: 'pending' as const,
        created_at: '2024-01-03',
    },
];

describe('Approvals Index page', () => {
    it('renders empty state', () => {
        setCurrentUser(1);
        render(<ApprovalsIndex approvals={[]} />);
        expect(screen.getByText(/no pending approvals/i)).toBeInTheDocument();
    });

    it('renders requester IDs', () => {
        setCurrentUser(1);
        render(<ApprovalsIndex approvals={approvals} />);
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('renders permission names', () => {
        setCurrentUser(1);
        render(<ApprovalsIndex approvals={approvals} />);
        expect(screen.getByText('vps.write')).toBeInTheDocument();
    });

    it('renders reason text', () => {
        setCurrentUser(1);
        render(<ApprovalsIndex approvals={approvals} />);
        expect(screen.getByText('Need to restart VPS')).toBeInTheDocument();
    });

    it('renders — for null reason', () => {
        setCurrentUser(1);
        render(<ApprovalsIndex approvals={[approvals[1]!]} />);
        expect(screen.getByText('—')).toBeInTheDocument();
    });

    it('renders status badges', () => {
        setCurrentUser(1);
        render(<ApprovalsIndex approvals={approvals} />);
        expect(screen.getAllByText('pending').length).toBeGreaterThan(0);
        expect(screen.getByText('approved')).toBeInTheDocument();
    });

    it('shows Approve button for pending approvals where current user is not requester', () => {
        setCurrentUser(1); // current user id=1; ap-1 requester is '2'
        render(<ApprovalsIndex approvals={[approvals[0]!]} />);
        expect(screen.getByRole('button', { name: /approve/i })).toBeInTheDocument();
    });

    it('shows "Awaiting review" for own pending requests', () => {
        setCurrentUser(1); // current user id=1; ap-3 requester is '1'
        render(<ApprovalsIndex approvals={[approvals[2]!]} />);
        expect(screen.getByText(/awaiting review/i)).toBeInTheDocument();
    });

    it('renders rejected status badge', () => {
        setCurrentUser(1);
        const rejected = { ...approvals[0]!, id: 'ap-r', status: 'rejected' as const };
        render(<ApprovalsIndex approvals={[rejected]} />);
        expect(screen.getByText('rejected')).toBeInTheDocument();
    });

    it('calls router.post on Approve click', async () => {
        setCurrentUser(1);
        render(<ApprovalsIndex approvals={[approvals[0]!]} />);
        await userEvent.click(screen.getByRole('button', { name: /approve/i }));
        expect(vi.mocked(router.post)).toHaveBeenCalledWith('/governance/approvals/ap-1/approve');
    });
});
