<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request, EntitlementService $entitlements): View
    {
        return view('subscriptions.index', [
            'currentPeriod' => $entitlements->currentPeriodFor($request->user()),
            'plans' => Plan::query()
                ->where('is_active', true)
                ->where('is_public', true)
                ->with([
                    'features',
                    'prices' => fn ($priceQuery) => $priceQuery
                        ->where('starts_at', '<=', now())
                        ->where(fn ($endsQuery) => $endsQuery->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                        ->orderByDesc('starts_at'),
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
