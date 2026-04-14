import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import VpsShow from '../Show';

const vps = {
    id: 'vps-1',
    hostname: 'web-server-01',
    plan: 'KVM 2',
    status: 'running',
    ip_address: '192.168.1.1',
    region: 'us-east',
    os: 'Ubuntu 22.04',
    cpus: 2,
    ram: 4096,
    disk: 80,
};

const metrics = {
    cpu_usage: 45.5,
    memory_usage: 60.0,
    disk_usage: 30.0,
    network_in: 1024 * 1024 * 10,
    network_out: 1024 * 1024 * 5,
};

const actions = [
    { id: 'a1', type: 'start', state: 'success', started_at: '2024-01-01', completed_at: '2024-01-01' },
    { id: 'a2', type: 'reboot', state: 'error', started_at: '2024-01-02', completed_at: null },
];

const backups = [
    { id: 'b1', created_at: '2024-01-01', size: 1024 * 1024 * 500, state: 'completed' },
    { id: 'b2', created_at: '2024-01-02', size: 1024 * 1024 * 200, state: 'pending' },
];

const defaultProps = { vps, metrics, actions, backups };

describe('VPS Show page', () => {
    it('renders hostname in header', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getAllByText('web-server-01').length).toBeGreaterThan(0);
    });

    it('renders Details tab content by default', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getByText('VPS Details')).toBeInTheDocument();
        expect(screen.getByText('KVM 2')).toBeInTheDocument();
        expect(screen.getByText('192.168.1.1')).toBeInTheDocument();
    });

    it('renders sub-navigation links', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getByRole('link', { name: /firewall/i })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /ssh keys/i })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /snapshots/i })).toBeInTheDocument();
    });

    it('switches to Metrics tab', async () => {
        render(<VpsShow {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /metrics/i }));
        expect(screen.getByText('Resource Usage')).toBeInTheDocument();
        expect(screen.getByText('45.5%')).toBeInTheDocument();
    });

    it('shows "No metrics available" when metrics is null', async () => {
        render(<VpsShow {...defaultProps} metrics={null} />);
        await userEvent.click(screen.getByRole('button', { name: /metrics/i }));
        expect(screen.getByText(/no metrics available/i)).toBeInTheDocument();
    });

    it('switches to Actions tab', async () => {
        render(<VpsShow {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /^actions$/i }));
        expect(screen.getByText('start')).toBeInTheDocument();
        expect(screen.getByText('reboot')).toBeInTheDocument();
    });

    it('shows "No actions recorded" when empty', async () => {
        render(<VpsShow {...defaultProps} actions={[]} />);
        await userEvent.click(screen.getByRole('button', { name: /^actions$/i }));
        expect(screen.getByText(/no actions recorded/i)).toBeInTheDocument();
    });

    it('switches to Backups tab', async () => {
        render(<VpsShow {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /backups/i }));
        expect(screen.getByText('2024-01-01')).toBeInTheDocument();
        expect(screen.getByText('500 MB')).toBeInTheDocument();
    });

    it('shows "No backups available" when empty', async () => {
        render(<VpsShow {...defaultProps} backups={[]} />);
        await userEvent.click(screen.getByRole('button', { name: /backups/i }));
        expect(screen.getByText(/no backups available/i)).toBeInTheDocument();
    });

    it('renders stopped status in details tab', () => {
        render(<VpsShow {...defaultProps} vps={{ ...vps, status: 'stopped' }} />);
        expect(screen.getByText('stopped')).toBeInTheDocument();
    });

    it('renders starting status in details tab', () => {
        render(<VpsShow {...defaultProps} vps={{ ...vps, status: 'starting' }} />);
        expect(screen.getByText('starting')).toBeInTheDocument();
    });

    it('renders unknown status in details tab', () => {
        render(<VpsShow {...defaultProps} vps={{ ...vps, status: 'migrating' }} />);
        expect(screen.getByText('migrating')).toBeInTheDocument();
    });

    it('shows all metric gauges', async () => {
        render(<VpsShow {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /metrics/i }));
        expect(screen.getByText('CPU')).toBeInTheDocument();
        expect(screen.getByText('Memory')).toBeInTheDocument();
        expect(screen.getByText('Disk')).toBeInTheDocument();
    });
});
