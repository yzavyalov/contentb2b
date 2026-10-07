<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MerchantSignedHttpService
{
    public function post(
        Merchant $merchant,
        string $url,
        array $payload
    ): Response {
        $token = $merchant->tokens()
            ->where('is_active', true)
            ->whereNotNull('token_encrypted')
            ->latest('id')
            ->first();

        if (! $token) {
            throw new RuntimeException(
                "Merchant #{$merchant->id} has no active API token."
            );
        }

        try {
            $plainToken = Crypt::decryptString(
                $token->token_encrypted
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                "Unable to decrypt API token for merchant #{$merchant->id}.",
                0,
                $e
            );
        }

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );

        $timestamp = (string) time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . '.' . $json,
            $plainToken
        );

        return Http::acceptJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Wrangle-Timestamp' => $timestamp,
                'X-Wrangle-Signature' => $signature,
            ])
            ->timeout(20)
            ->withBody($json, 'application/json')
            ->post($url);
    }
}
