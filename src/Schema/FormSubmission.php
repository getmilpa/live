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
 * The result of a bind: the normalized (typed where valid, raw-or-null where invalid/absent) `values`
 * for every field, plus the `validation` outcome. This is what a later dispatch seam consumes — never
 * this package.
 */
final readonly class FormSubmission
{
    /** @param array<string, mixed> $values */
    public function __construct(public array $values, public ValidationResult $validation)
    {
    }
}
