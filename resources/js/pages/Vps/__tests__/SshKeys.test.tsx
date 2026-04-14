import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import VpsSshKeys from '../SshKeys';

const vps = { id: 'vps-1', hostname: 'web-01', plan: 'KVM 2', status: 'running', ip_address: '1.2.3.4' };

const keys = [
    { id: 1, name: 'My laptop', fingerprint: 'SHA256:abc123', created_at: '2024-01-01' },
    { id: 2, name: 'CI server', fingerprint: 'SHA256:def456', created_at: '2024-01-02' },
];

describe('VPS SSH Keys page', () => {
    it('renders empty state when no keys', () => {
        render(<VpsSshKeys vps={vps} keys={[]} />);
        expect(screen.getByText(/no ssh keys associated/i)).toBeInTheDocument();
    });

    it('renders key name', () => {
        render(<VpsSshKeys vps={vps} keys={keys} />);
        expect(screen.getByText('My laptop')).toBeInTheDocument();
        expect(screen.getByText('CI server')).toBeInTheDocument();
    });

    it('renders key fingerprint', () => {
        render(<VpsSshKeys vps={vps} keys={keys} />);
        expect(screen.getByText('SHA256:abc123')).toBeInTheDocument();
    });

    it('renders added date', () => {
        render(<VpsSshKeys vps={vps} keys={keys} />);
        expect(screen.getByText('Added 2024-01-01')).toBeInTheDocument();
    });
});
