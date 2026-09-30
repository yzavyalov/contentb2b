<?php

namespace App\Enums;

enum MerchantSubscriptionChangeReason: string
{
    /**
     * Subscription was assigned manually
     * by a financial manager.
     */
    case MANUAL = 'manual';

    /**
     * Default Start plan assigned automatically
     * when a merchant was created.
     */
    case MERCHANT_CREATED = 'merchant_created';

    /**
     * Merchant was automatically moved to the
     * default Start plan because there was not
     * enough balance to pay the recurring fee.
     */
    case INSUFFICIENT_BALANCE = 'insufficient_balance';

    /**
     * Merchant was automatically restored to the
     * previous recurring plan after sufficient
     * balance became available again.
     */
    case BALANCE_RESTORED = 'balance_restored';
}
