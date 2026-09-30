<?php

namespace App\Enums;

enum MerchantBalanceTransactionType: string
{
    case CRYPTO_DEPOSIT = 'crypto_deposit';
    case BANK_DEPOSIT = 'bank_deposit';

    case SUBSCRIPTION_CHARGE = 'subscription_charge';
    case MARKET_CHARGE = 'market_charge';

    case REFUND = 'refund';

    case MANUAL_CREDIT = 'manual_credit';
    case MANUAL_DEBIT = 'manual_debit';

    public function label(): string
    {
        return match ($this) {
            self::CRYPTO_DEPOSIT => 'Crypto Deposit',
            self::BANK_DEPOSIT => 'Bank Deposit',
            self::SUBSCRIPTION_CHARGE => 'Subscription Charge',
            self::MARKET_CHARGE => 'Market Charge',
            self::REFUND => 'Refund',
            self::MANUAL_CREDIT => 'Manual Credit',
            self::MANUAL_DEBIT => 'Manual Debit',
        };
    }

    public function isCredit(): bool
    {
        return match ($this) {
            self::CRYPTO_DEPOSIT,
            self::BANK_DEPOSIT,
            self::REFUND,
            self::MANUAL_CREDIT => true,

            default => false,
        };
    }

    public function isDebit(): bool
    {
        return ! $this->isCredit();
    }
}
