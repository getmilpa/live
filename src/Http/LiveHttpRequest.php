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
 * Framework-agnostic input for {@see LiveEndpoint}. Deliberately built from
 * scalars/arrays (not `StateSnapshot`/`InteractionRequest`) because the
 * caller — an untrusted HTTP client — has not been verified yet; the
 * endpoint is what turns this into trusted value objects, or rejects it.
 */
final readonly class LiveHttpRequest
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $method,
        public string $action,
        public string $stateEnvelope,
        public array $payload,
        public string $sessionId,
        public string $csrfToken,
    ) {
    }
}
