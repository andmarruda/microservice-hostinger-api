<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Modules\HostingerProxyModule\UseCases\GetBillingCatalog\GetBillingCatalog;
use App\Modules\HostingerProxyModule\UseCases\GetPaymentMethods\GetPaymentMethods;
use App\Modules\HostingerProxyModule\UseCases\GetSubscriptions\GetSubscriptions;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class BillingPageController extends Controller
{
    public function __construct(
        private GetBillingCatalog $getBillingCatalog,
        private GetPaymentMethods $getPaymentMethods,
        private GetSubscriptions $getSubscriptions,
    ) {}

    public function index(Request $request): Response
    {
        $user          = $request->user();
        $catalog       = $this->getBillingCatalog->execute($user);
        $paymentMethods = $this->getPaymentMethods->execute($user);
        $subscriptions  = $this->getSubscriptions->execute($user);

        return Inertia::render('Billing/Index', [
            'catalog'        => $catalog->success ? $catalog->data : [],
            'paymentMethods' => $paymentMethods->success ? $paymentMethods->data : [],
            'subscriptions'  => $subscriptions->success ? $subscriptions->data : [],
        ]);
    }
}
