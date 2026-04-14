import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import VpsFirewall from '../Firewall';

const vps = { id: 'vps-1', hostname: 'web-01', plan: 'KVM 2', status: 'running', ip_address: '1.2.3.4' };

const rules = [
    {
        id: 1,
        direction: 'inbound' as const,
        protocol: 'tcp',
        port_range_min: 80,
        port_range_max: 80,
        source: '0.0.0.0/0',
        action: 'accept',
    },
    {
        id: 2,
        direction: 'outbound' as const,
        protocol: 'udp',
        port_range_min: 53,
        port_range_max: 53,
        destination: '8.8.8.8',
        action: 'accept',
    },
    {
        id: 3,
        direction: 'inbound' as const,
        protocol: 'tcp',
        port_range_min: 22,
        port_range_max: 22,
        source: '10.0.0.0/8',
        action: 'drop',
    },
];

describe('VPS Firewall page', () => {
    it('renders empty state when no rules', () => {
        render(<VpsFirewall vps={vps} rules={[]} />);
        expect(screen.getByText(/no firewall rules configured/i)).toBeInTheDocument();
    });

    it('renders rule protocol', () => {
        render(<VpsFirewall vps={vps} rules={rules} />);
        expect(screen.getAllByText('tcp').length).toBeGreaterThan(0);
    });

    it('renders rule direction badges', () => {
        render(<VpsFirewall vps={vps} rules={rules} />);
        expect(screen.getAllByText('inbound').length).toBeGreaterThan(0);
        expect(screen.getByText('outbound')).toBeInTheDocument();
    });

    it('renders action badges (accept/drop)', () => {
        render(<VpsFirewall vps={vps} rules={rules} />);
        expect(screen.getAllByText('accept').length).toBeGreaterThan(0);
        expect(screen.getByText('drop')).toBeInTheDocument();
    });

    it('renders port numbers', () => {
        render(<VpsFirewall vps={vps} rules={rules} />);
        expect(screen.getAllByText(/^80$/).length).toBeGreaterThan(0);
    });

    it('renders source for inbound rule', () => {
        render(<VpsFirewall vps={vps} rules={[rules[0]!]} />);
        expect(screen.getByText('0.0.0.0/0')).toBeInTheDocument();
    });

    it('renders destination for outbound rule', () => {
        render(<VpsFirewall vps={vps} rules={[rules[1]!]} />);
        expect(screen.getByText('8.8.8.8')).toBeInTheDocument();
    });
});
