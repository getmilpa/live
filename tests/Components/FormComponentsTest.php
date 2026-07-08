<?php

/**
 * This file is part of Milpa Live — the render-target-agnostic live component core of the Milpa PHP framework.
 *
 * (c) TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

namespace Milpa\Live\Tests\Components;

use Milpa\Live\Components\Form\CheckboxComponent;
use Milpa\Live\Components\Form\InputComponent;
use Milpa\Live\Components\Form\SelectComponent;
use Milpa\Live\Components\Form\TextareaComponent;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\InteractionRequest;
use PHPUnit\Framework\TestCase;

/**
 * Converted from `tests/smoke.php` lines ~955-986 (`InputComponent` and
 * `CheckboxComponent` mount/handle). `TextareaComponent`/`SelectComponent`
 * were only exercised indirectly there (via `XhtmlComponentCompiler`, a
 * `milpa/live-web` concern) — this class adds direct mount()/handle()
 * coverage for all four `AbstractFieldComponent` subclasses so the
 * core-owned behavior (shared change/blur/reset/set-error handling) has its
 * own package-level proof independent of any HTML renderer.
 */
final class FormComponentsTest extends TestCase
{
    public function testInputMountAndChange(): void
    {
        $input = new InputComponent();
        $state = $input->mount(
            ['name' => 'project_name', 'label' => 'Project', 'required' => true],
            new ComponentContext('project_name', route: '/lab/form'),
        );

        self::assertSame('input', $state->componentName);
        self::assertSame('', $state->data['value']);
        self::assertTrue($state->meta['required']);

        $changed = $input->handle(new InteractionRequest(
            componentId: 'project_name',
            componentName: 'input',
            action: 'change',
            state: $state,
            payload: ['value' => 'Milpa Forms'],
        ));

        self::assertSame('Milpa Forms', $changed->state->data['value']);
        self::assertTrue($changed->state->data['dirty']);
    }

    public function testCheckboxMountAndChange(): void
    {
        $checkbox = new CheckboxComponent();
        $state = $checkbox->mount(
            ['name' => 'approved', 'label' => 'Approved'],
            new ComponentContext('approved', route: '/lab/form'),
        );

        self::assertFalse($state->data['checked']);

        $changed = $checkbox->handle(new InteractionRequest(
            componentId: 'approved',
            componentName: 'checkbox',
            action: 'change',
            state: $state,
            payload: ['checked' => true],
        ));

        self::assertTrue($changed->state->data['checked']);
    }

    public function testTextareaMountAndChange(): void
    {
        $textarea = new TextareaComponent();
        $state = $textarea->mount(
            ['name' => 'brief', 'label' => 'Brief', 'rows' => 6],
            new ComponentContext('brief', route: '/lab/form'),
        );

        self::assertSame('textarea', $state->componentName);
        self::assertSame(6, $state->meta['rows']);

        $changed = $textarea->handle(new InteractionRequest(
            componentId: 'brief',
            componentName: 'textarea',
            action: 'change',
            state: $state,
            payload: ['value' => 'A short brief.'],
        ));

        self::assertSame('A short brief.', $changed->state->data['value']);
        self::assertTrue($changed->state->data['dirty']);
    }

    public function testSelectMountResolvesOptionsAndHandlesChange(): void
    {
        $select = new SelectComponent();
        $state = $select->mount([
            'name' => 'stage',
            'label' => 'Stage',
            'options' => [
                ['value' => 'discovery', 'label' => 'Discovery'],
                ['value' => 'prototype', 'label' => 'Prototype'],
            ],
        ], new ComponentContext('stage', route: '/lab/form'));

        self::assertSame('select', $state->componentName);
        self::assertSame('prototype', $state->meta['options'][1]['value']);

        $changed = $select->handle(new InteractionRequest(
            componentId: 'stage',
            componentName: 'select',
            action: 'change',
            state: $state,
            payload: ['value' => 'prototype'],
        ));

        self::assertSame('prototype', $changed->state->data['value']);
    }

    public function testBlurMarksTouchedWithoutChangingValue(): void
    {
        $input = new InputComponent();
        $state = $input->mount(['name' => 'email'], new ComponentContext('email'));

        $blurred = $input->handle(new InteractionRequest(
            componentId: 'email',
            componentName: 'input',
            action: 'blur',
            state: $state,
        ));

        self::assertTrue($blurred->state->data['touched']);
        self::assertSame($state->data['value'], $blurred->state->data['value']);
    }

    public function testResetRestoresTheMountTimeDefaultValue(): void
    {
        $input = new InputComponent();
        $state = $input->mount(['name' => 'email', 'value' => 'default@milpa.test'], new ComponentContext('email'));

        $changed = $input->handle(new InteractionRequest(
            componentId: 'email',
            componentName: 'input',
            action: 'change',
            state: $state,
            payload: ['value' => 'typed@milpa.test'],
        ));
        $reset = $input->handle(new InteractionRequest(
            componentId: 'email',
            componentName: 'input',
            action: 'reset',
            state: $changed->state,
        ));

        self::assertSame('default@milpa.test', $reset->state->data['value']);
        self::assertFalse($reset->state->data['dirty']);
    }

    public function testSetErrorRecordsAndClearsAFieldError(): void
    {
        $input = new InputComponent();
        $state = $input->mount(['name' => 'email'], new ComponentContext('email'));

        $withError = $input->handle(new InteractionRequest(
            componentId: 'email',
            componentName: 'input',
            action: 'set-error',
            state: $state,
            payload: ['error' => 'Invalid email address.'],
        ));

        self::assertSame('Invalid email address.', $withError->state->data['error']);

        $cleared = $input->handle(new InteractionRequest(
            componentId: 'email',
            componentName: 'input',
            action: 'set-error',
            state: $withError->state,
            payload: ['error' => null],
        ));

        self::assertNull($cleared->state->data['error']);
    }

    public function testUnknownActionReportsAnErrorInsteadOfThrowing(): void
    {
        $input = new InputComponent();
        $state = $input->mount(['name' => 'email'], new ComponentContext('email'));

        $result = $input->handle(new InteractionRequest(
            componentId: 'email',
            componentName: 'input',
            action: 'drop-database',
            state: $state,
        ));

        self::assertArrayHasKey('action', $result->errors);
    }
}
