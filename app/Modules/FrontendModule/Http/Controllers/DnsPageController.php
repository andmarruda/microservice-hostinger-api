<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetDnsSnapshots\GetDnsSnapshots;
use App\Modules\HostingerProxyModule\UseCases\GetDnsZone\GetDnsZone;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DnsPageController extends Controller
{
    public function __construct(
        private GetDnsZone $getDnsZone,
        private GetDnsSnapshots $getDnsSnapshots,
    ) {}

    public function zone(Request $request, string $domain): Response
    {
        $user      = $request->user();
        $zone      = $this->getDnsZone->execute($user, $domain);
        $snapshots = $this->getDnsSnapshots->execute($user, $domain);

        return Inertia::render('Dns/Zone', [
            'domain'    => $domain,
            'records'   => $zone->success ? $zone->data : [],
            'snapshots' => $snapshots->success ? $snapshots->data : [],
        ]);
    }
}
