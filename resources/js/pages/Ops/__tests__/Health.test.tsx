import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import OpsHealth from '../Health';

const allOkServices = [
    { name: 'Database', status: 'ok' as const },
    { name: 'Cache', status: 'ok' as const },
    { name: 'Queue', status: 'ok' as const },
];

const degradedServices = [
    { name: 'Database', status: 'ok' as const },
    { name: 'Cache', status: 'degraded' as const, message: 'High memory usage' },
    { name: 'Queue', status: 'down' as const, message: 'Workers offline' },
];

describe('Ops Health page', () => {
    it('shows "All systems operational" when all ok', () => {
        render(<OpsHealth services={allOkServices} checkedAt="2024-01-01T12:00:00Z" />);
        expect(screen.getByText(/all systems operational/i)).toBeInTheDocument();
    });

    it('shows "Degraded service" when any service is down', () => {
        render(<OpsHealth services={degradedServices} checkedAt="2024-01-01T12:00:00Z" />);
        expect(screen.getByText(/degraded service/i)).toBeInTheDocument();
    });

    it('renders service names', () => {
        render(<OpsHealth services={allOkServices} checkedAt="2024-01-01" />);
        expect(screen.getByText('Database')).toBeInTheDocument();
        expect(screen.getByText('Cache')).toBeInTheDocument();
        expect(screen.getByText('Queue')).toBeInTheDocument();
    });

    it('renders ok status badges', () => {
        render(<OpsHealth services={allOkServices} checkedAt="2024-01-01" />);
        expect(screen.getAllByText('ok').length).toBe(3);
    });

    it('renders degraded status badge', () => {
        render(<OpsHealth services={degradedServices} checkedAt="2024-01-01" />);
        expect(screen.getByText('degraded')).toBeInTheDocument();
    });

    it('renders down status badge', () => {
        render(<OpsHealth services={degradedServices} checkedAt="2024-01-01" />);
        expect(screen.getByText('down')).toBeInTheDocument();
    });

    it('renders service messages', () => {
        render(<OpsHealth services={degradedServices} checkedAt="2024-01-01" />);
        expect(screen.getByText('High memory usage')).toBeInTheDocument();
        expect(screen.getByText('Workers offline')).toBeInTheDocument();
    });

    it('renders checkedAt timestamp', () => {
        render(<OpsHealth services={allOkServices} checkedAt="2024-01-01T12:00:00Z" />);
        expect(screen.getByText(/checked at 2024-01-01/i)).toBeInTheDocument();
    });

    it('renders empty services list without crashing', () => {
        render(<OpsHealth services={[]} checkedAt="2024-01-01" />);
        expect(screen.getByText(/all systems operational/i)).toBeInTheDocument();
    });
});
