import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import OpsDatabase from '../Database';

const tables = [
    { name: 'infra_audit_logs', rows: 15000, retention_days: 90 },
    { name: 'drift_reports', rows: 42, retention_days: 90 },
    { name: 'access_reviews', rows: 5, retention_days: 730 },
    { name: 'failed_jobs', rows: 0, retention_days: 30 },
    { name: 'users', rows: 10, retention_days: null },
];

describe('Ops Database page', () => {
    it('renders table names', () => {
        render(<OpsDatabase tables={tables} />);
        expect(screen.getByText('infra_audit_logs')).toBeInTheDocument();
        expect(screen.getByText('drift_reports')).toBeInTheDocument();
    });

    it('renders row counts formatted with locale', () => {
        render(<OpsDatabase tables={tables} />);
        expect(screen.getByText('15,000')).toBeInTheDocument();
    });

    it('renders retention days', () => {
        render(<OpsDatabase tables={tables} />);
        expect(screen.getAllByText('90').length).toBeGreaterThan(0);
        expect(screen.getByText('730')).toBeInTheDocument();
        expect(screen.getByText('30')).toBeInTheDocument();
    });

    it('renders ∞ for null retention', () => {
        render(<OpsDatabase tables={tables} />);
        expect(screen.getByText('∞')).toBeInTheDocument();
    });

    it('renders zero row count', () => {
        render(<OpsDatabase tables={[tables[3]!]} />);
        expect(screen.getByText('0')).toBeInTheDocument();
    });

    it('renders empty tables list without crashing', () => {
        render(<OpsDatabase tables={[]} />);
        expect(screen.getByText(/table row counts/i)).toBeInTheDocument();
    });
});
