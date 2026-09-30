<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\User;

class CurrentMerchant
{
    private ?Merchant $resolved = null;

    private bool $wasResolved = false;

    /**
     * Return the currently selected merchant.
     *
     * null means that the user is currently in
     * "All Merchants" mode.
     */
    public function get(?User $user = null): ?Merchant
    {
        if ($this->wasResolved) {
            return $this->resolved;
        }

        $this->wasResolved = true;

        $user ??= auth()->user();

        if (! $user) {
            return $this->resolved = null;
        }

        $selectedMerchantId = session('merchant_id');

        /*
         |--------------------------------------------------------------------------
         | ALL MERCHANTS MODE
         |--------------------------------------------------------------------------
         |
         | No merchant_id in the session means that no specific merchant
         | is selected.
         |
         | This is intentional.
         |
         | The Merchant Dashboard can use this state to display aggregated
         | statistics across all merchants owned by the authenticated user.
         |
         */
        if (! $selectedMerchantId) {
            return $this->resolved = null;
        }

        /*
         |--------------------------------------------------------------------------
         | SECURITY
         |--------------------------------------------------------------------------
         |
         | Never trust merchant_id from the session directly.
         |
         | Always resolve the merchant through the authenticated user's
         | merchants() relation.
         |
         */
        $merchant = $user->merchants()
            ->whereKey($selectedMerchantId)
            ->first();

        if ($merchant) {
            return $this->resolved = $merchant;
        }

        /*
         |--------------------------------------------------------------------------
         | INVALID / NO LONGER OWNED MERCHANT
         |--------------------------------------------------------------------------
         |
         | The merchant may have been deleted or transferred to another user.
         |
         | In that case we clear the selected merchant and safely return to
         | "All Merchants" mode.
         |
         */
        session()->forget('merchant_id');

        return $this->resolved = null;
    }

    /**
     * Return selected merchant ID.
     *
     * null = All Merchants mode.
     */
    public function id(?User $user = null): ?int
    {
        return $this->get($user)?->id;
    }

    /**
     * Determine whether a specific merchant is currently selected.
     */
    public function hasSelection(?User $user = null): bool
    {
        return $this->get($user) !== null;
    }

    /**
     * Determine whether the user is currently viewing
     * the aggregated "All Merchants" context.
     */
    public function isAll(?User $user = null): bool
    {
        return $this->get($user) === null;
    }

    /**
     * Select a merchant.
     */
    public function set(
        Merchant $merchant,
        ?User $user = null
    ): Merchant {
        $user ??= auth()->user();

        if (! $user) {
            abort(401);
        }

        /*
         |--------------------------------------------------------------------------
         | SECURITY CHECK
         |--------------------------------------------------------------------------
         |
         | Do not trust the Merchant model passed to this method.
         |
         | Verify that this merchant actually belongs to the authenticated
         | user before storing its ID in the session.
         |
         */
        $ownedMerchant = $user->merchants()
            ->whereKey($merchant->id)
            ->first();

        if (! $ownedMerchant) {
            abort(
                403,
                'You do not have access to this merchant.'
            );
        }

        session([
            'merchant_id' => $ownedMerchant->id,
        ]);

        $this->resolved = $ownedMerchant;
        $this->wasResolved = true;

        return $ownedMerchant;
    }

    /**
     * Switch to "All Merchants" mode.
     */
    public function all(): void
    {
        session()->forget('merchant_id');

        /*
         * Important:
         * null is now a valid resolved state.
         *
         * We therefore keep wasResolved=true so that another call to get()
         * during the same request does not attempt to resolve anything else.
         */
        $this->resolved = null;
        $this->wasResolved = true;
    }

    /**
     * Clear the cached state and selected merchant.
     *
     * After this method get() will resolve the context again.
     * Since merchant_id is absent, that will result in All Merchants mode.
     */
    public function clear(): void
    {
        session()->forget('merchant_id');

        $this->resolved = null;
        $this->wasResolved = false;
    }
}
