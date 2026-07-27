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

namespace Milpa\Live\Schema;

/**
 * The static shape of one form field derived from a schema property: its name (the property key),
 * base type, human label, whether it is required, its default, and its constraints.
 */
final readonly class FormField
{
    public function __construct(
        public string $name,
        public FieldType $type,
        public string $label,
        public bool $required,
        public mixed $default,
        public FieldConstraints $constraints,
    ) {
    }
}
