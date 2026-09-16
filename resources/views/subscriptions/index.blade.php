<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-semibold tracking-wide text-indigo-600">ACCOUNT</p>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Subscription and access</h2>
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto flex max-w-5xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                @if ($currentPeriod)
                    <p class="text-sm font-semibold text-emerald-700">Active access</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ $currentPeriod->subscription->plan->name }}</h1>
                    <p class="mt-2 text-sm text-slate-600">Your current access ends {{ $currentPeriod->ends_at->toFormattedDateString() }}.</p>
                @else
                    <p class="text-sm font-semibold text-amber-700">No active subscription</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Your learning access is not active yet.</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Plan checkout will be added when M-Pesa billing is ready. This page intentionally does not promise a trial or charge your account.</p>
                @endif
            </section>

            <section>
                <div class="flex flex-col gap-2">
                    <h2 class="text-xl font-semibold text-slate-900">Available plans</h2>
                    <p class="text-sm text-slate-600">Choose a monthly plan when M-Pesa checkout is available.</p>
                </div>

                @if ($plans->isEmpty())
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-sm text-slate-600">No public plans are configured yet.</div>
                @else
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach ($plans as $plan)
                            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                @php($currentPrice = $plan->prices->first())
                                <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                                @if ($plan->description)
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $plan->description }}</p>
                                @endif
                                @if ($currentPrice)
                                    <p class="mt-4 text-2xl font-semibold text-slate-900">KES {{ number_format($currentPrice->amount_minor / 100) }}<span class="text-sm font-medium text-slate-500"> / {{ $currentPrice->billing_interval }}</span></p>
                                @endif
                                <ul class="mt-4 flex flex-col gap-2 text-sm text-slate-700">
                                    @foreach ($plan->features as $feature)
                                        <li>{{ $feature->feature_code }}: {{ $feature->allowance === null ? 'Unlimited' : $feature->allowance }}</li>
                                    @endforeach
                                </ul>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
