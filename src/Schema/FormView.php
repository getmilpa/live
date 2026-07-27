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
 * The visual state of a form to render: which fields (definition), with which values, and with which
 * validation outcome. It is pure render input — the renderer renders state, it never binds. The same
 * FormView drives the no-JS transport (P5.3a) and the live transport (P5.3b).
 */
final readonly class FormView
{
    /** @param array<string, mixed> $values */
    public function __construct(
        public FormDefinition $definition,
        public array $values,
        public ValidationResult $validation,
    ) {
    }
}
