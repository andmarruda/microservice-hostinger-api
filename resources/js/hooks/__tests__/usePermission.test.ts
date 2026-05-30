import { usePage } from '@inertiajs/react';
import { renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { usePermission } from '../usePermission';

function mockPage(roles: string[], permissions: string[]) {
    vi.mocked(usePage).mockReturnValue({
        props: {
            name: 'Hostinger',
            auth: {
                user: { id: 1, name: 'User', email: 'u@e.com', email_verified_at: null, created_at: '', updated_at: '' },
                roles,
                permissions,
            },
            flash: { success: null, error: null },
        },
        url: '/',
        component: '',
        version: null,
    } as ReturnType<typeof usePage>);
}

describe('usePermission', () => {
    describe('can()', () => {
        it('returns true when permission is in list', () => {
            mockPage([], ['vps.read', 'vps.write']);
            const { result } = renderHook(() => usePermission());
            expect(result.current.can('vps.read')).toBe(true);
        });

        it('returns false when permission is not in list', () => {
            mockPage([], ['vps.read']);
            const { result } = renderHook(() => usePermission());
            expect(result.current.can('vps.write')).toBe(false);
        });

        it('returns false for empty permissions array', () => {
            mockPage([], []);
            const { result } = renderHook(() => usePermission());
            expect(result.current.can('anything')).toBe(false);
        });

        it('returns true for admin even when permission is not listed', () => {
            mockPage(['admin'], []);
            const { result } = renderHook(() => usePermission());
            expect(result.current.can('anything')).toBe(true);
        });
    });

    describe('is()', () => {
        it('returns true when role is in list', () => {
            mockPage(['admin', 'root'], []);
            const { result } = renderHook(() => usePermission());
            expect(result.current.is('admin')).toBe(true);
        });

        it('returns false when role is not in list', () => {
            mockPage(['admin'], []);
            const { result } = renderHook(() => usePermission());
            expect(result.current.is('root')).toBe(false);
        });

        it('returns false for empty roles array', () => {
            mockPage([], []);
            const { result } = renderHook(() => usePermission());
            expect(result.current.is('admin')).toBe(false);
        });
    });

    describe('isAdmin()', () => {
        it('returns true when roles contains admin', () => {
            mockPage(['admin'], []);
            const { result } = renderHook(() => usePermission());
            expect(result.current.isAdmin()).toBe(true);
        });

        it('returns false when roles does not contain admin', () => {
            mockPage(['user', 'viewer'], []);
            const { result } = renderHook(() => usePermission());
            expect(result.current.isAdmin()).toBe(false);
        });

        it('returns false for empty roles', () => {
            mockPage([], []);
            const { result } = renderHook(() => usePermission());
            expect(result.current.isAdmin()).toBe(false);
        });
    });
});
