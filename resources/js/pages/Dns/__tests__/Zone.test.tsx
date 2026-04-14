import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import DnsZone from '../Zone';

const records = [
    { id: 'r1', type: 'A', name: '@', content: '1.2.3.4', ttl: 3600 },
    { id: 'r2', type: 'AAAA', name: '@', content: '::1', ttl: 3600 },
    { id: 'r3', type: 'CNAME', name: 'www', content: 'example.com', ttl: 1800 },
    { id: 'r4', type: 'MX', name: '@', content: 'mail.example.com', ttl: 3600, priority: 10 },
    { id: 'r5', type: 'TXT', name: '@', content: 'v=spf1 include:spf.example.com ~all', ttl: 3600 },
    { id: 'r6', type: 'NS', name: '@', content: 'ns1.example.com', ttl: 86400 },
];

describe('DNS Zone page', () => {
    it('renders domain name', () => {
        render(<DnsZone domain="example.com" records={records} />);
        expect(screen.getAllByText('example.com').length).toBeGreaterThan(0);
    });

    it('shows total record count', () => {
        render(<DnsZone domain="example.com" records={records} />);
        expect(screen.getByText('(6 total)')).toBeInTheDocument();
    });

    it('renders empty state when no records', () => {
        render(<DnsZone domain="example.com" records={[]} />);
        expect(screen.getByText(/no dns records found/i)).toBeInTheDocument();
    });

    it('renders record types as badges', () => {
        render(<DnsZone domain="example.com" records={records} />);
        expect(screen.getByText('A')).toBeInTheDocument();
        expect(screen.getByText('CNAME')).toBeInTheDocument();
        expect(screen.getByText('MX')).toBeInTheDocument();
    });

    it('renders record names', () => {
        render(<DnsZone domain="example.com" records={records} />);
        expect(screen.getByText('www')).toBeInTheDocument();
    });

    it('renders record content', () => {
        render(<DnsZone domain="example.com" records={records} />);
        expect(screen.getByText('1.2.3.4')).toBeInTheDocument();
    });

    it('renders TTL values', () => {
        render(<DnsZone domain="example.com" records={records} />);
        expect(screen.getAllByText('3600s').length).toBeGreaterThan(0);
    });

    it('renders MX priority', () => {
        render(<DnsZone domain="example.com" records={records} />);
        expect(screen.getByText('10')).toBeInTheDocument();
    });

    it('renders — for records without priority', () => {
        render(<DnsZone domain="example.com" records={[records[0]!]} />);
        expect(screen.getByText('—')).toBeInTheDocument();
    });
});
