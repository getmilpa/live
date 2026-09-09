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

namespace Milpa\Live\Tests\ValueObjects;

use Milpa\Live\Components\Autocomplete\AutocompleteComponent;
use Milpa\Live\Components\Dashboard\DashboardShellComponent;
use Milpa\Live\Components\Dashboard\DashboardSidebarComponent;
use Milpa\Live\Components\Dashboard\DashboardTopbarComponent;
use Milpa\Live\Components\Dashboard\DataTableComponent;
use Milpa\Live\Components\StateMachineComponent;
use PHPUnit\Framework\TestCase;

/**
 * Every component of this package that has words of its own declares where they live.
 *
 * The house shipped twelve hardcoded human-facing strings across its own component system, eleven of
 * them Spanish — in a package whose stated default is English. Four were `aria-label`s, which means an
 * English screen-reader user was read Spanish and nobody could see it happening. These are the ones
 * that had words; a component with no words declares no catalogue, and that is not an omission.
 */
final class ComponentMessagesDeclarationTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: array<int, string>}>
     */
    public static function componentsWithWords(): array
    {
        return [
            'autocomplete' => [AutocompleteComponent::class, ['remove', 'search_placeholder', 'no_results']],
            'dashboard-shell' => [DashboardShellComponent::class, ['skip_to_content']],
            'dashboard-sidebar' => [DashboardSidebarComponent::class, ['nav_label', 'section_label']],
            'dashboard-topbar' => [DashboardTopbarComponent::class, ['open_navigation', 'search']],
            'data-table' => [DataTableComponent::class, ['selected', 'select_row']],
            'state-machine' => [StateMachineComponent::class, ['state']],
        ];
    }

    /**
     * @param class-string      $component
     * @param array<int,string> $keys
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('componentsWithWords')]
    public function testTheCatalogueIsDeclaredShippedAndCompleteInBothLocales(string $component, array $keys): void
    {
        $path = $component::contract()->presentation()->messages;

        self::assertNotNull($path, $component . ' has words and declares no catalogue');
        self::assertFileExists($path, $component . ' names a catalogue it does not ship');

        $catalogue = require $path;

        self::assertArrayHasKey('en', $catalogue, 'English is the default and cannot be the missing one');
        self::assertArrayHasKey('es', $catalogue);

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $catalogue['en'], $key . ' has no English default');
            self::assertNotSame('', trim((string) $catalogue['en'][$key]));
        }

        // Spanish may lag — the fallback covers it — but a key it does invent answers to nothing.
        foreach (array_keys($catalogue['es']) as $key) {
            self::assertArrayHasKey($key, $catalogue['en'], $key . ' exists only in Spanish, so nothing falls back to it');
        }
    }

    /**
     * The English default must actually be English, which is the defect this arc is fixing.
     *
     * @param class-string      $component
     * @param array<int,string> $keys
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('componentsWithWords')]
    public function testTheEnglishDefaultIsNotTheSpanishStringWearingItsName(string $component, array $keys): void
    {
        $catalogue = require (string) $component::contract()->presentation()->messages;

        foreach ($keys as $key) {
            self::assertNotSame(
                $catalogue['es'][$key] ?? null,
                $catalogue['en'][$key],
                $key . ' reads the same in both, which is how the Spanish original hides as the default',
            );
        }
    }
}
