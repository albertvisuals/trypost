<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Enums\SocialAccount\Platform;
use App\Exceptions\PlatformUnavailableException;
use App\Exceptions\TokenExpiredException;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Normalizes the failure modes of an OAuth token-refresh request:
 *
 * - ConnectionException (timeout / DNS / refused) → PlatformUnavailableException
 * - HTTP 5xx                                      → PlatformUnavailableException
 * - HTTP 4xx                                      → TokenExpiredException
 *
 * Callers configure the actual HTTP call through the closure passed to
 * `send()`, so platform-specific quirks (form vs JSON body, auth headers,
 * basic auth, etc.) stay where they belong — in the per-platform refresh
 * method — while the failure semantics are uniform across providers.
 */
class TokenRefreshClient
{
    public function __construct(public readonly Platform $platform) {}

    public static function for(Platform $platform): self
    {
        return new self($platform);
    }

    /**
     * @param  Closure():Response  $request
     *
     * @throws PlatformUnavailableException
     * @throws TokenExpiredException
     */
    public function send(Closure $request): Response
    {
        $name = $this->platform->label();

        try {
            $response = $request();
        } catch (ConnectionException $e) {
            throw new PlatformUnavailableException("{$name} API unreachable: {$e->getMessage()}");
        }

        if ($response->serverError()) {
            throw new PlatformUnavailableException(
                "{$name} API returned {$response->status()} during token refresh",
                $response->status(),
            );
        }

        if ($response->failed()) {
            Log::error("TokenRefreshClient: {$name} token refresh failed", [
                'body' => $this->redactBody($response->body()),
            ]);
            throw new TokenExpiredException("Failed to refresh {$name} token");
        }

        return $response;
    }

    private function redactBody(string $body): string
    {
        return preg_replace(
            [
                '/access_token=([^&"\s]+)/',
                '/"access_token"\s*:\s*"([^"]+)"/',
                '/Bearer\s+\S+/',
                '/"token"\s*:\s*"([^"]+)"/',
            ],
            [
                'access_token=[REDACTED]',
                '"access_token":"[REDACTED]"',
                'Bearer [REDACTED]',
                '"token":"[REDACTED]"',
            ],
            $body
        );
    }
}
