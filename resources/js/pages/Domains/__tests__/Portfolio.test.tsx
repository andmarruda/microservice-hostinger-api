import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import DomainsPortfolio from '../Portfolio';

const domains = [
    { domain: 'example.com', status: 'active', expires_at: '2025-01-01', auto_renew: true },
    { domain: 'test.org', status: 'expiring_soon', expires_at: '2024-02-01', auto_renew: false },
    { domain: 'old.net', status: 'expired', expires_at: '2024-01-01', auto_renew: false },
];

describe('Domains Portfolio page', () => {
    it('renders empty state when no domains', () => {
        render(<DomainsPortfolio domains={[]} />);
        expect(screen.getByText(/no domains found/i)).toBeInTheDocument();
    });

    it('renders domain names', () => {
        render(<DomainsPortfolio domains={domains} />);
        expect(screen.getByText('example.com')).toBeInTheDocument();
        expect(screen.getByText('test.org')).toBeInTheDocument();
    });

    it('renders active status badge', () => {
        render(<DomainsPortfolio domains={[domains[0]!]} />);
        expect(screen.getByText('active')).toBeInTheDocument();
    });

    it('renders expiring_soon status badge', () => {
        render(<DomainsPortfolio domains={[domains[1]!]} />);
        expect(screen.getByText('expiring_soon')).toBeInTheDocument();
    });

    it('renders expired status badge', () => {
        render(<DomainsPortfolio domains={[domains[2]!]} />);
        expect(screen.getByText('expired')).toBeInTheDocument();
    });

    it('renders auto-renew on badge', () => {
        render(<DomainsPortfolio domains={[domains[0]!]} />);
        expect(screen.getByText('on')).toBeInTheDocument();
    });

    it('renders auto-renew off badge', () => {
        render(<DomainsPortfolio domains={[domains[1]!]} />);
        expect(screen.getByText('off')).toBeInTheDocument();
    });

    it('renders expiry dates', () => {
        render(<DomainsPortfolio domains={[domains[0]!]} />);
        expect(screen.getByText('2025-01-01')).toBeInTheDocument();
    });

    it('renders DNS Zone links', () => {
        render(<DomainsPortfolio domains={domains} />);
        expect(screen.getAllByRole('link', { name: /dns zone/i }).length).toBe(3);
    });

    it('renders Check availability link', () => {
        render(<DomainsPortfolio domains={[]} />);
        expect(screen.getByRole('link', { name: /check availability/i })).toBeInTheDocument();
    });
});
