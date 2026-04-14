import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import OpsCache from '../Cache';

const stats = [
    { key: 'vps_list', hits: 100, misses: 10 },
    { key: 'domain_portfolio', hits: 0, misses: 50 },
    { key: 'billing_catalog', hits: 80, misses: 20 },
];

describe('Ops Cache page', () => {
    it('renders empty state when no stats', () => {
        render(<OpsCache stats={[]} />);
        expect(screen.getByText(/no cache stats recorded yet/i)).toBeInTheDocument();
    });

    it('renders cache keys', () => {
        render(<OpsCache stats={stats} />);
        expect(screen.getByText('vps_list')).toBeInTheDocument();
        expect(screen.getByText('domain_portfolio')).toBeInTheDocument();
    });

    it('renders hit counts', () => {
        render(<OpsCache stats={stats} />);
        expect(screen.getByText('100')).toBeInTheDocument();
    });

    it('renders miss counts', () => {
        render(<OpsCache stats={stats} />);
        expect(screen.getByText('10')).toBeInTheDocument();
    });

    it('renders hit rate percentages', () => {
        render(<OpsCache stats={stats} />);
        // vps_list: 100/(100+10) = 90%
        expect(screen.getByText('91%')).toBeInTheDocument();
    });

    it('renders 0% for zero hits', () => {
        render(<OpsCache stats={[stats[1]!]} />);
        // domain_portfolio: 0/(0+50) = 0%
        expect(screen.getByText('0%')).toBeInTheDocument();
    });

    it('renders green hit rate for high rates', () => {
        render(<OpsCache stats={[stats[0]!]} />);
        // 91% >= 80, should be green
        const el = screen.getByText('91%');
        expect(el).toHaveClass('text-green-600');
    });

    it('renders red hit rate for low rates', () => {
        render(<OpsCache stats={[stats[1]!]} />);
        const el = screen.getByText('0%');
        expect(el).toHaveClass('text-red-500');
    });
});
