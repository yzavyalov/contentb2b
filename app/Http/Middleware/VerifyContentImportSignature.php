<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyContentImportSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.content_import.secret');

        if (!is_string($secret) || strlen($secret) < 32) {
            report(new \RuntimeException(
                'Content import secret is not configured correctly.'
            ));

            return response()->json([
                'message' => 'Import service unavailable.',
            ], 503);
        }

        $timestamp = $request->header('X-Content-Timestamp');
        $requestId = $request->header('X-Content-Request-Id');
        $signature = $request->header('X-Content-Signature');

        if (
            !is_string($timestamp) ||
            !ctype_digit($timestamp) ||
            !is_string($requestId) ||
            !preg_match(
                '/^[a-f0-9-]{36}$/i',
                $requestId
            ) ||
            !is_string($signature) ||
            !preg_match('/^[a-f0-9]{64}$/i', $signature)
        ) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return response()->json([
                'message' => 'Request expired.',
            ], 401);
        }

        $payload = $timestamp
            . "\n"
            . $requestId
            . "\n"
            . $request->getContent();

        $expectedSignature = hash_hmac(
            'sha256',
            $payload,
            $secret
        );

        if (!hash_equals($expectedSignature, strtolower($signature))) {
            return response()->json([
                'message' => 'Invalid signature.',
            ], 401);
        }

        $cacheKey = 'content_import:request:' . strtolower($requestId);

        if (!Cache::add($cacheKey, true, now()->addMinutes(10))) {
            return response()->json([
                'message' => 'Duplicate request.',
            ], 409);
        }

        return $next($request);
    }
}
