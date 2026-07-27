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
 * The public entry point: parse a tool/operation input schema into a FormDefinition, and bind raw
 * input into a typed, validated FormSubmission. A thin facade over SchemaFormParser + FormBinder;
 * `bind` deliberately lives here (and on the binder), never on the FormDefinition value object.
 * SchemaForm neither renders nor executes.
 */
final class SchemaForm
{
    public function __construct(
        private readonly SchemaFormParser $parser = new SchemaFormParser(),
        private readonly FormBinder $binder = new FormBinder(),
    ) {
    }

    /**
     * Parses an input schema into a FormDefinition (delegates to SchemaFormParser).
     *
     * @param array<string, mixed> $inputSchema
     */
    public function fromSchema(string $id, array $inputSchema): FormDefinition
    {
        return $this->parser->fromSchema($id, $inputSchema);
    }

    /**
     * Binds raw values against a definition into a FormSubmission (delegates to FormBinder).
     *
     * @param array<string, mixed> $rawValues
     */
    public function bind(FormDefinition $definition, array $rawValues): FormSubmission
    {
        return $this->binder->bind($definition, $rawValues);
    }
}
