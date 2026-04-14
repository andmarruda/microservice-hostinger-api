import { useForm } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import VpsIndex from '../Index';

const runningVps = {
    id: 'vps-1',
    hostname: 'web-server-01',
    plan: 'KVM 2',
    status: 'running',
    ip_address: '192.168.1.1',
};

const stoppedVps = {
    id: 'vps-2',
    hostname: 'db-server-01',
    plan: 'KVM 4',
    status: 'stopped',
    ip_address: '192.168.1.2',
};

describe('VPS Index page', () => {
    it('renders empty state when no VPS', () => {
        render(<VpsIndex vps={[]} />);
        expect(screen.getByText(/no vps instances found/i)).toBeInTheDocument();
    });

    it('renders VPS hostname', () => {
        render(<VpsIndex vps={[runningVps]} />);
        expect(screen.getAllByText('web-server-01').length).toBeGreaterThan(0);
    });

    it('renders VPS plan', () => {
        render(<VpsIndex vps={[runningVps]} />);
        expect(screen.getByText('KVM 2')).toBeInTheDocument();
    });

    it('renders VPS IP address', () => {
        render(<VpsIndex vps={[runningVps]} />);
        expect(screen.getByText('192.168.1.1')).toBeInTheDocument();
    });

    it('renders running status badge', () => {
        render(<VpsIndex vps={[runningVps]} />);
        expect(screen.getByText('running')).toBeInTheDocument();
    });

    it('shows Reboot and Stop buttons for running VPS', () => {
        render(<VpsIndex vps={[runningVps]} />);
        expect(screen.getByRole('button', { name: /reboot/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /stop/i })).toBeInTheDocument();
    });

    it('shows Start button for stopped VPS', () => {
        render(<VpsIndex vps={[stoppedVps]} />);
        expect(screen.getByRole('button', { name: /start/i })).toBeInTheDocument();
    });

    it('calls post with start URL on Start click', async () => {
        const post = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: {},
            setData: vi.fn(),
            post,
            errors: {},
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<VpsIndex vps={[stoppedVps]} />);
        await userEvent.click(screen.getByRole('button', { name: /start/i }));
        expect(post).toHaveBeenCalledWith('/vps/vps-2/start');
    });

    it('calls post with stop URL on Stop click', async () => {
        const post = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: {},
            setData: vi.fn(),
            post,
            errors: {},
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<VpsIndex vps={[runningVps]} />);
        await userEvent.click(screen.getByRole('button', { name: /stop/i }));
        expect(post).toHaveBeenCalledWith('/vps/vps-1/stop');
    });

    it('renders multiple VPS', () => {
        render(<VpsIndex vps={[runningVps, stoppedVps]} />);
        expect(screen.getAllByText('web-server-01').length).toBeGreaterThan(0);
        expect(screen.getAllByText('db-server-01').length).toBeGreaterThan(0);
    });

    it('renders starting status badge', () => {
        render(<VpsIndex vps={[{ ...runningVps, status: 'starting' }]} />);
        expect(screen.getByText('starting')).toBeInTheDocument();
    });
});
