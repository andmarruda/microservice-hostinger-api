// ─── Auth & User ─────────────────────────────────────────────────────────────

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

export interface Auth {
    user: User;
    roles: string[];
    permissions: string[];
}

export interface Flash {
    success: string | null;
    error: string | null;
}

export interface SharedData {
    name: string;
    auth: Auth;
    flash: Flash;
    [key: string]: unknown;
}

// ─── VPS ─────────────────────────────────────────────────────────────────────

export interface Vps {
    id: string;
    hostname: string;
    plan: string;
    status: string;
    ip_address: string;
    region?: string;
    os?: string;
    cpus?: number;
    ram?: number;
    disk?: number;
    [key: string]: unknown;
}

export interface VpsAccessGrant {
    id: number;
    user_id: number;
    vps_id: string;
    granted_by: number;
    granted_at: string;
    expires_at: string | null;
    stale_at: string | null;
}

export interface FirewallRule {
    id?: string | number;
    direction: 'inbound' | 'outbound';
    protocol: string;
    port_range_min: number | string;
    port_range_max: number | string;
    source?: string;
    destination?: string;
    action: string;
    [key: string]: unknown;
}

export interface SshKey {
    id: string | number;
    name: string;
    fingerprint: string;
    created_at: string;
    [key: string]: unknown;
}

export interface Snapshot {
    id: string | number;
    name: string;
    status: string;
    size?: number;
    created_at: string;
    [key: string]: unknown;
}

// ─── Governance ───────────────────────────────────────────────────────────────

export interface AccessReview {
    id: string;
    reviewer_id: string;
    status: 'pending' | 'completed' | 'cancelled';
    period_start?: string;
    period_end?: string;
    completed_at?: string | null;
    created_at: string;
    items?: AccessReviewItem[];
    reviewer?: User;
}

export interface AccessReviewItem {
    id: string;
    review_id: string;
    user_id: string;
    vps_id: string;
    granted_at: string;
    expires_at: string | null;
    decision: 'approved' | 'revoked' | null;
    decided_at: string | null;
    decided_by: string | null;
    user?: User;
}

export interface PermissionApproval {
    id: string;
    requester_id: string;
    target_user_id?: string;
    permission: string;
    vps_id?: string | null;
    status: 'pending' | 'approved' | 'rejected';
    approved_by?: string | null;
    decided_at?: string | null;
    reason: string | null;
    created_at: string;
    requester?: User;
    targetUser?: User;
}

export interface InfraAuditLog {
    id: number;
    action: string;
    actor_id: number | null;
    actor_email: string | null;
    vps_id?: string | null;
    resource_type: string | null;
    resource_id: string | null;
    correlation_id?: string | null;
    outcome: string;
    ip_address?: string | null;
    performed_at: string;
    created_at?: string;
}

// ─── Drift ────────────────────────────────────────────────────────────────────

export interface DriftReport {
    id: number;
    drift_type: string;
    severity: string;
    vps_id: string | null;
    user_id: number | null;
    status: 'open' | 'resolved';
    detected_at: string;
    details: Record<string, unknown> | null;
}

// ─── Ops ──────────────────────────────────────────────────────────────────────

export interface QuotaStats {
    vps_used: number;
    vps_limit: number;
    domains_used: number;
    domains_limit: number;
    dns_records_used: number;
    dns_records_limit: number;
    snapshots_used: number;
    snapshots_limit: number;
    ssh_keys_used: number;
    ssh_keys_limit: number;
    firewall_rules_used: number;
    firewall_rules_limit: number;
    [key: string]: number;
}

export interface CacheKeyStats {
    key: string;
    hits: number;
    misses: number;
}

// ─── Billing ──────────────────────────────────────────────────────────────────

export interface BillingItem {
    id: string | number;
    name: string;
    price: number;
    currency: string;
    billing_cycle: string;
    description?: string;
    [key: string]: unknown;
}

export interface Subscription {
    id: string | number;
    name: string;
    status: string;
    price: number;
    currency: string;
    billing_cycle: string;
    next_billing_date?: string | null;
    [key: string]: unknown;
}
