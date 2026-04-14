<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetDomainAvailability\GetDomainAvailability;
use App\Modules\HostingerProxyModule\UseCases\GetDomainPortfolio\GetDomainPortfolio;
use App\Modules\HostingerProxyModule\UseCases\GetWhois\GetWhois;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DomainPageController extends Controller
{
    public function __construct(
        private GetDomainPortfolio $getDomainPortfolio,
        private GetDomainAvailability $getDomainAvailability,
        private GetWhois $getWhois,
    ) {}

    public function portfolio(Request $request): Response
    {
        $result = $this->getDomainPortfolio->execute($request->user());

        return Inertia::render('Domains/Portfolio', [
            'domains' => $result->success ? $result->data : [],
            'error'   => !$result->success ? $result->error : null,
        ]);
    }

    public function availability(Request $request): Response
    {
        $domain = $request->query('domain', '');
        $result = null;

        if ($domain) {
            $check  = $this->getDomainAvailability->execute($request->user(), $domain);
            $result = $check->success ? $check->data : null;
        }

        return Inertia::render('Domains/Availability', [
            'domain'     => $domain,
            'result'     => $result,
        ]);
    }
}
