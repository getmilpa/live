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
 * The declarative constraints on a field. `enumOptions` (presence) means a renderer should offer a
 * select/radio; the values are type-consistent with the field's base FieldType.
 */
final readonly class FieldConstraints
{
    /** @param list<int|float|string>|null $enumOptions */
    public function __construct(
        public int|float|null $min = null,
        public int|float|null $max = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public ?RegexPattern $pattern = null,
        public ?array $enumOptions = null,
    ) {
    }
}
