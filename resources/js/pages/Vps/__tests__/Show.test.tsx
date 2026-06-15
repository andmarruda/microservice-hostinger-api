import { useForm } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
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

const sshKeys = [
    { id: 'key-1', name: 'anderson-laptop', fingerprint: 'SHA256:abc', created_at: '2024-01-01' },
];

const backups = [
    { id: 'backup-1', created_at: '2026-06-15', size: 1024, state: 'completed' },
];

const defaultProps = { vps, metrics, actions: [], backups, sshKeys };

describe('VPS Show page', () => {
    it('renders error message when vps is null', () => {
        render(<VpsShow {...defaultProps} vps={null} />);
        expect(screen.getByText(/vps details unavailable/i)).toBeInTheDocument();
    });

    it('renders hostname in header', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getAllByText('web-server-01').length).toBeGreaterThan(0);
    });

    it('renders IP address in header', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getAllByText(/192\.168\.1\.1/).length).toBeGreaterThan(0);
    });

    it('renders status badge in header', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getAllByText('running').length).toBeGreaterThan(0);
    });

    it('renders plan in header', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getByText(/KVM 2/)).toBeInTheDocument();
    });

    it('renders display_name in header when set', () => {
        render(<VpsShow {...defaultProps} vps={{ ...vps, display_name: 'My Web Box' }} />);
        expect(screen.getAllByText('My Web Box').length).toBeGreaterThan(0);
    });

    it('shows Reboot and Stop buttons for running VPS', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getByRole('button', { name: /reboot/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /stop/i })).toBeInTheDocument();
    });

    it('shows Start button for stopped VPS', () => {
        render(<VpsShow {...defaultProps} vps={{ ...vps, status: 'stopped' }} />);
        expect(screen.getByRole('button', { name: /start/i })).toBeInTheDocument();
    });

    it('no power buttons for transitional VPS status', () => {
        render(<VpsShow {...defaultProps} vps={{ ...vps, status: 'starting' }} />);
        expect(screen.queryByRole('button', { name: /^start$/i })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /^stop$/i })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /^reboot$/i })).not.toBeInTheDocument();
    });

    it('calls post with reboot URL on Reboot click', async () => {
        const post = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: {},
            setData: vi.fn(),
            post,
            put: vi.fn(),
            errors: {},
            processing: false,
            reset: vi.fn(),
        } as unknown as ReturnType<typeof useForm>);

        render(<VpsShow {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /reboot/i }));
        expect(post).toHaveBeenCalledWith('/vps/vps-1/reboot');
    });

    it('calls post with stop URL on Stop click', async () => {
        const post = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: {},
            setData: vi.fn(),
            post,
            put: vi.fn(),
            errors: {},
            processing: false,
            reset: vi.fn(),
        } as unknown as ReturnType<typeof useForm>);

        render(<VpsShow {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /stop/i }));
        expect(post).toHaveBeenCalledWith('/vps/vps-1/stop');
    });

    it('calls post with start URL on Start click for stopped VPS', async () => {
        const post = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: {},
            setData: vi.fn(),
            post,
            put: vi.fn(),
            errors: {},
            processing: false,
            reset: vi.fn(),
        } as unknown as ReturnType<typeof useForm>);

        render(<VpsShow {...defaultProps} vps={{ ...vps, status: 'stopped' }} />);
        await userEvent.click(screen.getByRole('button', { name: /start/i }));
        expect(post).toHaveBeenCalledWith('/vps/vps-1/start');
    });

    it('shows rename button (pencil) in header', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getByRole('button', { name: /rename/i })).toBeInTheDocument();
    });

    it('opens rename dialog when pencil button clicked', async () => {
        render(<VpsShow {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /rename/i }));
        expect(screen.getByRole('heading', { name: /rename vps/i })).toBeInTheDocument();
        expect(screen.getByLabelText(/display name/i)).toBeInTheDocument();
    });

    it('renders SSH Keys card with existing keys', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getByText('anderson-laptop')).toBeInTheDocument();
        expect(screen.getByText('SHA256:abc')).toBeInTheDocument();
    });

    it('shows Add Key button in SSH Keys card', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getByRole('button', { name: /add key/i })).toBeInTheDocument();
    });

    it('opens add key dialog when Add Key clicked', async () => {
        render(<VpsShow {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /add key/i }));
        expect(screen.getByLabelText(/key name/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/public key/i)).toBeInTheDocument();
    });

    it('shows remove button on SSH key', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getByRole('button', { name: /remove key/i })).toBeInTheDocument();
    });

    it('opens delete confirm dialog when remove button clicked', async () => {
        render(<VpsShow {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /remove key/i }));
        expect(screen.getByRole('heading', { name: /remove ssh key/i })).toBeInTheDocument();
        expect(screen.getAllByText(/anderson-laptop/i).length).toBeGreaterThan(0);
    });

    it('shows "No SSH keys" when empty', () => {
        render(<VpsShow {...defaultProps} sshKeys={[]} />);
        expect(screen.getByText(/no ssh keys on this vps/i)).toBeInTheDocument();
    });

    it('renders Change Password card', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getAllByText('Change Password').length).toBeGreaterThan(0);
        expect(screen.getByLabelText(/new password/i)).toBeInTheDocument();
    });

    it('renders metric stat cards when metrics provided', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getByText('CPU')).toBeInTheDocument();
        expect(screen.getByText('Memory')).toBeInTheDocument();
        expect(screen.getByText('Disk')).toBeInTheDocument();
    });

    it('does not render metric cards when metrics is null', () => {
        render(<VpsShow {...defaultProps} metrics={null} />);
        expect(screen.queryByText('CPU')).not.toBeInTheDocument();
        expect(screen.queryByText('Memory')).not.toBeInTheDocument();
    });

    it('renders resource error messages', () => {
        render(<VpsShow {...defaultProps} resourceErrors={{ metrics: 'Hostinger could not return metrics right now.' }} />);
        expect(screen.getByText(/could not return metrics/i)).toBeInTheDocument();
    });

    it('renders backups card', () => {
        render(<VpsShow {...defaultProps} />);
        expect(screen.getByText('Backups')).toBeInTheDocument();
        expect(screen.getByText('backup-1')).toBeInTheDocument();
    });

    it('renders stopped status', () => {
        render(<VpsShow {...defaultProps} vps={{ ...vps, status: 'stopped' }} />);
        expect(screen.getAllByText('stopped').length).toBeGreaterThan(0);
    });

    it('renders starting status', () => {
        render(<VpsShow {...defaultProps} vps={{ ...vps, status: 'starting' }} />);
        expect(screen.getAllByText('starting').length).toBeGreaterThan(0);
    });
});
