<?php

namespace App\Modules\PolicyModule;

final class PolicyActions
{
    const VPS_START = 'vps.start';
    const VPS_STOP = 'vps.stop';
    const VPS_REBOOT = 'vps.reboot';
    const FIREWALL_ADD = 'vps.firewall.add';
    const FIREWALL_REMOVE = 'vps.firewall.remove';
    const SSH_KEY_ADD = 'vps.ssh_key.add';
    const SSH_KEY_REMOVE = 'vps.ssh_key.remove';
    const SNAPSHOT_CREATE = 'vps.snapshot.create';
    const SNAPSHOT_DELETE = 'vps.snapshot.delete';

    const ALL = [
        self::VPS_START,
        self::VPS_STOP,
        self::VPS_REBOOT,
        self::FIREWALL_ADD,
        self::FIREWALL_REMOVE,
        self::SSH_KEY_ADD,
        self::SSH_KEY_REMOVE,
        self::SNAPSHOT_CREATE,
        self::SNAPSHOT_DELETE,
    ];
}
