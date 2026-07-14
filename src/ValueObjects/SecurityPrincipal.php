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

namespace Milpa\Live\ValueObjects;

/**
 * The authenticated caller behind a request, as resolved by a
 * {@see \Milpa\Live\Contracts\Security\TokenVerifierInterface} and
 * consulted by {@see \Milpa\Live\Contracts\Security\InteractionAuthorizerInterface}.
 * There is no "principal not authenticated" state modeled here — an
 * unauthenticated caller is represented by the *absence* of a
 * `SecurityPrincipal` (a `null` where one is expected), not an instance
 * with empty scopes.
 */
final readonly class SecurityPrincipal
{
    /**
     * @param string               $id     The principal's stable identifier.
     * @param array<int, string>   $scopes Granted scope strings (e.g. `'milpa:component:autocomplete:search'`);
     *                                     the wildcard `'milpa:*'` grants every scope, see {@see can()}.
     * @param array<string, mixed> $claims Additional caller-defined claims about this principal.
     */
    public function __construct(
        public string $id,
        public array $scopes = [],
        public array $claims = [],
    ) {
    }

    /**
     * Whether this principal has the given scope, either directly or via
     * the `'milpa:*'` wildcard scope.
     */
    public function can(string $scope): bool
    {
        return in_array($scope, $this->scopes, true) || in_array('milpa:*', $this->scopes, true);
    }
}
