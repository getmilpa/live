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
 * The base JSON-Schema type of a form field. `enum` is a CONSTRAINT (see FieldConstraints),
 * not a type — coercion always follows the base type here.
 */
enum FieldType: string
{
    case Text = 'text';        // JSON-Schema type: string
    case Integer = 'integer';  // type: integer
    case Number = 'number';    // type: number
    case Boolean = 'boolean';  // type: boolean
}
