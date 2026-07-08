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

namespace Milpa\Live\Tests\DataSource;

use Milpa\Live\DataSource\ArrayDataSource;
use Milpa\Live\DataSource\InMemoryDataSourceRegistry;
use Milpa\Live\ValueObjects\DataSourceRequest;
use PHPUnit\Framework\TestCase;

/**
 * Converted from `tests/smoke.php` lines ~118-124 (the bootstrap data
 * source setup shared by the rest of the lab's suite) plus direct coverage
 * of {@see ArrayDataSource::resolve()}'s filtering contract.
 */
final class ArrayDataSourceTest extends TestCase
{
    private function registry(): InMemoryDataSourceRegistry
    {
        $registry = new InMemoryDataSourceRegistry();
        $registry->register(new ArrayDataSource('customers.search', [
            ['value' => 'acme', 'label' => 'Acme Studio', 'search' => 'agency design'],
            ['value' => 'milpa', 'label' => 'Milpa Labs', 'search' => 'framework components'],
            ['value' => 'northwind', 'label' => 'Northwind', 'search' => 'commerce'],
        ]));

        return $registry;
    }

    public function testResolvesRegisteredSourceByName(): void
    {
        $source = $this->registry()->resolve('customers.search');

        self::assertTrue($source->supports('customers.search'));
        self::assertFalse($source->supports('unknown.source'));
    }

    public function testUnregisteredSourceThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->registry()->resolve('unknown.source');
    }

    public function testQueryFiltersAcrossLabelValueAndSearchFields(): void
    {
        $source = $this->registry()->resolve('customers.search');

        $result = $source->resolve(new DataSourceRequest(
            source: 'customers.search',
            componentId: 'customer-picker',
            query: 'mil',
            limit: 20,
        ));

        self::assertCount(1, $result->items);
        self::assertSame('milpa', $result->items[0]['value']);
    }

    public function testEmptyQueryReturnsEveryItemUpToLimit(): void
    {
        $source = $this->registry()->resolve('customers.search');

        $result = $source->resolve(new DataSourceRequest(
            source: 'customers.search',
            componentId: 'customer-picker',
            query: '',
            limit: 2,
        ));

        self::assertCount(2, $result->items);
        self::assertSame(3, $result->meta['total']);
        self::assertTrue($result->meta['truncated']);
    }
}
