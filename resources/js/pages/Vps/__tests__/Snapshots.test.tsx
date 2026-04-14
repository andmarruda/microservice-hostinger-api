import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import VpsSnapshots from '../Snapshots';

const vps = { id: 'vps-1', hostname: 'web-01', plan: 'KVM 2', status: 'running', ip_address: '1.2.3.4' };

const snapshots = [
    { id: 's1', name: 'snap-before-update', status: 'completed', size: 1024 * 1024 * 500, created_at: '2024-01-01' },
    { id: 's2', name: 'snap-daily', status: 'failed', size: undefined, created_at: '2024-01-02' },
    { id: 's3', name: 'snap-pending', status: 'pending', size: 1024 * 1024 * 200, created_at: '2024-01-03' },
];

describe('VPS Snapshots page', () => {
    it('renders empty state when no snapshots', () => {
        render(<VpsSnapshots vps={vps} snapshots={[]} />);
        expect(screen.getByText(/no snapshots found/i)).toBeInTheDocument();
    });

    it('renders snapshot name', () => {
        render(<VpsSnapshots vps={vps} snapshots={snapshots} />);
        expect(screen.getByText('snap-before-update')).toBeInTheDocument();
    });

    it('renders completed status badge', () => {
        render(<VpsSnapshots vps={vps} snapshots={snapshots} />);
        expect(screen.getByText('completed')).toBeInTheDocument();
    });

    it('renders failed status badge', () => {
        render(<VpsSnapshots vps={vps} snapshots={snapshots} />);
        expect(screen.getByText('failed')).toBeInTheDocument();
    });

    it('renders pending status badge', () => {
        render(<VpsSnapshots vps={vps} snapshots={snapshots} />);
        expect(screen.getByText('pending')).toBeInTheDocument();
    });

    it('renders size in MB', () => {
        render(<VpsSnapshots vps={vps} snapshots={[snapshots[0]!]} />);
        expect(screen.getByText('500 MB')).toBeInTheDocument();
    });

    it('renders — for missing size', () => {
        render(<VpsSnapshots vps={vps} snapshots={[snapshots[1]!]} />);
        expect(screen.getByText('—')).toBeInTheDocument();
    });

    it('renders creation date', () => {
        render(<VpsSnapshots vps={vps} snapshots={snapshots} />);
        expect(screen.getByText('2024-01-01')).toBeInTheDocument();
    });
});
