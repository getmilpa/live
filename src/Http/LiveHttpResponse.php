<?php

/**
 * This file is part of Milpa Live — the render-target-agnostic live component core of the Milpa PHP framework.
 *
 * (c) Rodrigo Vicente - TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

namespace Milpa\Live\Http;

/**
 * The HTTP-transport-agnostic response envelope for a live interaction —
 * a status code plus a JSON-serializable body, constructed only via
 * {@see ok()} or {@see error()} so every response carries the `ok` flag
 * the client-side runtime branches on.
 */
final readonly class LiveHttpResponse
{
    /**
     * @param array<string, mixed> $body
     */
    private function __construct(
        public int $status,
        public array $body,
    ) {
    }

    /**
     * A 200 success response; `$body` is merged after `'ok' => true`.
     *
     * @param array<string, mixed> $body
     */
    public static function ok(array $body): self
    {
        return new self(200, ['ok' => true, ...$body]);
    }

    /**
     * An `'ok' => false` error response for the given HTTP `$status`,
     * a machine-readable `$code`, a human-readable `$message`, and optional
     * structured `$details`.
     *
     * @param array<string, mixed> $details
     */
    public static function error(int $status, string $code, string $message, array $details = []): self
    {
        return new self($status, [
            'ok' => false,
            'error' => $code,
            'message' => $message,
            'details' => $details,
        ]);
    }
}
