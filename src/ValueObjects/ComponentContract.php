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
 * Machine-readable runtime contract for a live component.
 *
 * This is intentionally separate from @milpa/design visual contracts. A runtime
 * component may reference a visual contract, but it does not own design tokens.
 */
final readonly class ComponentContract
{
    /**
     * @param array<string, mixed> $propsSchema
     * @param array<string, mixed> $stateSchema
     * @param array<string, mixed> $actions
     * @param array<string, mixed> $dataSources
     */
    public function __construct(
        public string $name,
        public string $contractVersion,
        public string $summary = '',
        public ?string $designContract = null,
        public ?string $defaultTemplate = null,
        public array $propsSchema = [],
        public array $stateSchema = [],
        public array $actions = [],
        public array $dataSources = [],
    ) {
    }

    /**
     * One action, as a declaration, whichever form the component wrote.
     *
     * `$actions` carries two shapes on purpose and forever: the bare payload array every component has
     * written since this contract existed, and an {@see ActionContract} for one that declares what it is
     * FOR. Reading them here — rather than letting each consumer branch — is what makes the richer form
     * additive: the authorizer, the catalogue and any renderer ask one question and get one answer, and a
     * component that never migrates keeps working unchanged.
     *
     * A bare array becomes an ActionContract with nothing declared: `summary` empty, `mutating` false,
     * `effects` null. That is not a claim that the action is harmless — it is the honest record that nobody
     * said, which is what {@see ActionContract::declaresEffects()} exists to ask about.
     */
    public function action(string $name): ?ActionContract
    {
        if (!\array_key_exists($name, $this->actions)) {
            return null;
        }

        $spec = $this->actions[$name];
        if ($spec instanceof ActionContract) {
            return $spec;
        }

        $spec = \is_array($spec) ? $spec : [];
        $scopeBy = $spec['scopeBy'] ?? null;
        $payload = $spec['payload'] ?? null;

        return new ActionContract(
            scopeBy: \is_string($scopeBy) && $scopeBy !== '' ? $scopeBy : null,
            payload: \is_array($payload) ? $payload : [],
        );
    }
}
