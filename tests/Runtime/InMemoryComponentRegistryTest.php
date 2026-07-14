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

namespace Milpa\Live\Tests\Runtime;

use Milpa\Live\Components\Autocomplete\AutocompleteComponent;
use Milpa\Live\DataSource\InMemoryDataSourceRegistry;
use Milpa\Live\Runtime\InMemoryComponentRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Converted from `tests/smoke.php` lines ~252-256
 * (`InMemoryComponentRegistry` register/has/get).
 */
final class InMemoryComponentRegistryTest extends TestCase
{
    public function testRegisterHasAndGetRoundtrip(): void
    {
        $registry = new InMemoryComponentRegistry();
        $component = new AutocompleteComponent(new InMemoryDataSourceRegistry());

        self::assertFalse($registry->has('autocomplete'));

        $registry->register('autocomplete', $component);

        self::assertTrue($registry->has('autocomplete'));
        self::assertSame($component, $registry->get('autocomplete'));
    }

    public function testGetOnAnUnregisteredNameThrows(): void
    {
        $registry = new InMemoryComponentRegistry();

        $this->expectException(\RuntimeException::class);
        $registry->get('missing');
    }
}
