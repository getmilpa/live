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
 * The outcome of binding raw values against a FormDefinition. `ok` is true iff `errors` is empty.
 */
final readonly class ValidationResult
{
    /** @param array<string, list<FieldError>> $errors keyed by field name */
    public function __construct(public bool $ok, public array $errors)
    {
    }
}
