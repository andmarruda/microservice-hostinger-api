import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import AuditExport from '../AuditExport';

const logs = [
    {
        id: 1,
        action: 'vps.start',
        actor_id: 1,
        actor_email: 'alice@example.com',
        resource_type: 'vps',
        resource_id: 'vps-1',
        outcome: 'success',
        performed_at: '2024-01-01T10:00:00Z',
    },
    {
        id: 2,
        action: 'vps.stop',
        actor_id: 2,
        actor_email: 'bob@example.com',
        resource_type: 'vps',
        resource_id: 'vps-2',
        outcome: 'failure',
        performed_at: '2024-01-02T11:00:00Z',
    },
];

describe('Audit Export page', () => {
    it('renders empty state when no logs', () => {
        render(<AuditExport logs={[]} filters={{}} />);
        expect(screen.getByText(/no audit logs found/i)).toBeInTheDocument();
    });

    it('renders log actions', () => {
        render(<AuditExport logs={logs} filters={{}} />);
        expect(screen.getByText('vps.start')).toBeInTheDocument();
        expect(screen.getByText('vps.stop')).toBeInTheDocument();
    });

    it('renders actor emails', () => {
        render(<AuditExport logs={logs} filters={{}} />);
        expect(screen.getByText('alice@example.com')).toBeInTheDocument();
    });

    it('renders outcome badges', () => {
        render(<AuditExport logs={logs} filters={{}} />);
        expect(screen.getByText('success')).toBeInTheDocument();
        expect(screen.getByText('failure')).toBeInTheDocument();
    });

    it('renders resource type:id', () => {
        render(<AuditExport logs={logs} filters={{}} />);
        expect(screen.getByText('vps:vps-1')).toBeInTheDocument();
    });

    it('renders resource type alone when no resource_id', () => {
        render(<AuditExport logs={[{ ...logs[0]!, resource_id: null }]} filters={{}} />);
        expect(screen.getByText('vps')).toBeInTheDocument();
    });

    it('pre-fills filter inputs from filters prop', () => {
        render(<AuditExport logs={[]} filters={{ actor_id: 'abc-123', action: 'vps.start' }} />);
        expect(screen.getByDisplayValue('abc-123')).toBeInTheDocument();
        expect(screen.getByDisplayValue('vps.start')).toBeInTheDocument();
    });

    it('calls router.get on Filter submit', async () => {
        render(<AuditExport logs={[]} filters={{}} />);
        await userEvent.click(screen.getByRole('button', { name: /filter/i }));
        expect(vi.mocked(router.get)).toHaveBeenCalledWith(
            '/governance/audit',
            expect.any(Object),
            expect.any(Object),
        );
    });

    it('renders default outcome badge for unknown outcome', () => {
        render(<AuditExport logs={[{ ...logs[0]!, outcome: 'unknown' }]} filters={{}} />);
        expect(screen.getByText('unknown')).toBeInTheDocument();
    });

    it('updates actor ID filter on change', async () => {
        render(<AuditExport logs={[]} filters={{}} />);
        const input = screen.getByPlaceholderText('User UUID');
        await userEvent.type(input, 'abc');
        expect(input).toHaveValue('abc');
    });

    it('updates action filter on change', async () => {
        render(<AuditExport logs={[]} filters={{}} />);
        const input = screen.getByPlaceholderText('e.g. vps.start');
        await userEvent.type(input, 'vps.start');
        expect(input).toHaveValue('vps.start');
    });

    it('updates from date filter on change', async () => {
        render(<AuditExport logs={[]} filters={{}} />);
        const dateInputs = document.querySelectorAll('input[type="date"]');
        await userEvent.type(dateInputs[0]!, '2024-01-01');
        expect((dateInputs[0] as HTMLInputElement).value).toBe('2024-01-01');
    });

    it('updates to date filter on change', async () => {
        render(<AuditExport logs={[]} filters={{}} />);
        const dateInputs = document.querySelectorAll('input[type="date"]');
        await userEvent.type(dateInputs[1]!, '2024-12-31');
        expect((dateInputs[1] as HTMLInputElement).value).toBe('2024-12-31');
    });

    it('sets window.location.href on Download CSV click', async () => {
        render(<AuditExport logs={[]} filters={{}} />);
        await userEvent.click(screen.getByRole('button', { name: /download csv/i }));
        expect(window.location.href).toContain('/governance/audit/export');
        expect(window.location.href).toContain('format=csv');
    });
});
