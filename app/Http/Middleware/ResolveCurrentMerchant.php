<?php

namespace App\Http\Middleware;

use App\Services\CurrentMerchant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentMerchant
{
    public function __construct(
        private readonly CurrentMerchant $currentMerchant
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        /*
         * Resolve current merchant.
         *
         * If the user has only one merchant, it will automatically
         * become current.
         *
         * If the user has several merchants, the previously selected
         * one from the session will be used.
         */
        $merchant = $this->currentMerchant->get($user);

        /*
         * Make current merchant available to Blade views.
         */
        view()->share('currentMerchant', $merchant);

        return $next($request);
    }
}
