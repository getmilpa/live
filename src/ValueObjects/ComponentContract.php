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
}
