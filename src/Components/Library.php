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

namespace Milpa\Live\Components;

use Milpa\Live\Contracts\Component\DeclaresComponents;

/**
 * The primitives this package brings, declared the same way any plugin declares its own.
 *
 * The framework's own catalogue entry has no privileged path: it implements
 * {@see DeclaresComponents} like everyone else, so a catalogue that can see this can see a
 * plugin's components too, and vice versa — one mechanism, not a special case plus an extension
 * point.
 *
 * The list is asserted against the directory by
 * {@see \Milpa\Live\Tests\Components\LibraryIsTheDirectoryTest}: a component added here and not
 * declared, or declared and deleted, turns the suite red. Without that test this is a hand-kept
 * copy of `ls src/Components`, which is the second source of truth this package refuses
 * everywhere else.
 */
final class Library implements DeclaresComponents
{
    /**
     * The primitives this package brings — the concrete component classes under `src/Components`.
     *
     * Abstract bases are absent on purpose: a catalogue cannot mount one, and a declaration is a
     * promise that the class can be built.
     *
     * @return list<class-string<\Milpa\Live\Contracts\Component\ComponentDefinitionInterface>>
     */
    public function declaredComponents(): array
    {
        return [
            Autocomplete\AutocompleteComponent::class,
            Dashboard\DashboardActionButtonComponent::class,
            Dashboard\DashboardAlertListComponent::class,
            Dashboard\DashboardGridComponent::class,
            Dashboard\DashboardMainComponent::class,
            Dashboard\DashboardPageHeaderComponent::class,
            Dashboard\DashboardPanelComponent::class,
            Dashboard\DashboardShellComponent::class,
            Dashboard\DashboardSidebarComponent::class,
            Dashboard\DashboardTopbarComponent::class,
            Dashboard\DataTableComponent::class,
            Dashboard\MetricCardComponent::class,
            Form\CheckboxComponent::class,
            Form\InputComponent::class,
            Form\SelectComponent::class,
            Form\TextareaComponent::class,
            StateMachineComponent::class,
        ];
    }
}
