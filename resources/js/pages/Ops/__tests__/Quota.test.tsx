import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import OpsQuota from '../Quota';

const quota = {
    vps_used: 5,
    vps_limit: 10,
    domains_used: 3,
    domains_limit: 20,
    dns_records_used: 50,
    dns_records_limit: 500,
    snapshots_used: 2,
    snapshots_limit: 10,
    ssh_keys_used: 4,
    ssh_keys_limit: 50,
    firewall_rules_used: 8,
    firewall_rules_limit: 100,
};

describe('Ops Quota page', () => {
    it('renders VPS usage', () => {
        render(<OpsQuota quota={quota} />);
        expect(screen.getByText('VPS Instances')).toBeInTheDocument();
        expect(screen.getByText('5 / 10 (50%)')).toBeInTheDocument();
    });

    it('renders Domains usage', () => {
        render(<OpsQuota quota={quota} />);
        expect(screen.getAllByText('Domains').length).toBeGreaterThan(0);
        expect(screen.getByText('3 / 20 (15%)')).toBeInTheDocument();
    });

    it('renders DNS Records usage', () => {
        render(<OpsQuota quota={quota} />);
        expect(screen.getByText('DNS Records')).toBeInTheDocument();
        expect(screen.getByText('50 / 500 (10%)')).toBeInTheDocument();
    });

    it('renders Snapshots usage', () => {
        render(<OpsQuota quota={quota} />);
        expect(screen.getByText('Snapshots')).toBeInTheDocument();
    });

    it('renders SSH Keys usage', () => {
        render(<OpsQuota quota={quota} />);
        expect(screen.getByText('SSH Keys')).toBeInTheDocument();
    });

    it('renders Firewall Rules usage', () => {
        render(<OpsQuota quota={quota} />);
        expect(screen.getByText('Firewall Rules')).toBeInTheDocument();
    });

    it('renders green bars for low usage', () => {
        const { container } = render(<OpsQuota quota={{ ...quota, vps_used: 5, vps_limit: 100 }} />);
        expect(container.querySelectorAll('.bg-green-500').length).toBeGreaterThan(0);
    });

    it('renders yellow bar at ~70-89%', () => {
        const { container } = render(<OpsQuota quota={{ ...quota, vps_used: 75, vps_limit: 100 }} />);
        expect(container.querySelector('.bg-yellow-500')).toBeInTheDocument();
    });

    it('renders red bar at 90%+', () => {
        const { container } = render(<OpsQuota quota={{ ...quota, vps_used: 95, vps_limit: 100 }} />);
        expect(container.querySelector('.bg-red-500')).toBeInTheDocument();
    });

    it('handles zero limit gracefully', () => {
        render(<OpsQuota quota={{ ...quota, vps_used: 0, vps_limit: 0 }} />);
        expect(screen.getByText('0 / 0 (0%)')).toBeInTheDocument();
    });
});
