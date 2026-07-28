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

namespace Milpa\Live\Tests\Schema;

use Milpa\Live\Schema\FieldConstraints;
use Milpa\Live\Schema\FieldType;
use Milpa\Live\Schema\FormDefinition;
use Milpa\Live\Schema\FormField;
use Milpa\Live\Schema\FormView;
use Milpa\Live\Schema\ValidationResult;
use PHPUnit\Framework\TestCase;

final class FormViewTest extends TestCase
{
    public function test_holds_definition_values_and_validation(): void
    {
        $def = new FormDefinition('settings:update', [
            new FormField('siteName', FieldType::Text, 'Site name', true, null, new FieldConstraints()),
        ]);
        $view = new FormView($def, ['siteName' => 'Acme'], new ValidationResult(true, []));

        self::assertSame($def, $view->definition);
        self::assertSame('Acme', $view->values['siteName']);
        self::assertTrue($view->validation->ok);
    }
}
