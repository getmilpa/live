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

namespace Milpa\Live\Tests\Fixtures;

use Milpa\Live\Schema\FieldType;
use Milpa\Live\Schema\FormDefinition;

/**
 * A trivial HTML renderer used ONLY by tests to prove a FormDefinition carries enough to drive a
 * surface. It is NOT a public API, NOT shipped in src/, and NOT the official web renderer (that is a
 * later slice). Do not depend on it outside tests.
 */
final class ReferenceHtmlRenderer
{
    public function render(FormDefinition $definition): string
    {
        $html = '<form id="' . htmlspecialchars($definition->id) . '">';
        foreach ($definition->fields as $field) {
            $name = htmlspecialchars($field->name);
            if ($field->constraints->enumOptions !== null) {
                $html .= '<select name="' . $name . '">';
                foreach ($field->constraints->enumOptions as $opt) {
                    $html .= '<option value="' . htmlspecialchars((string) $opt) . '">' . htmlspecialchars((string) $opt) . '</option>';
                }
                $html .= '</select>';
                continue;
            }
            $html .= match ($field->type) {
                FieldType::Boolean => '<input type="checkbox" name="' . $name . '">',
                FieldType::Integer, FieldType::Number => '<input type="number" name="' . $name . '">',
                FieldType::Text => '<input type="text" name="' . $name . '">',
            };
        }

        return $html . '</form>';
    }
}
