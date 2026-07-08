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

use Milpa\Live\Components\Autocomplete\AutocompleteComponent;
use Milpa\Live\DataSource\ArrayDataSource;
use Milpa\Live\DataSource\InMemoryDataSourceRegistry;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\InteractionRequest;
use PHPUnit\Framework\TestCase;

/**
 * Converted from `tests/smoke.php` lines ~118-194 (the
 * mount/search/select/remove/clear sequence). The codec/HTML-rendering
 * assertions that followed in the lab (lines ~196-244) are NOT ported here
 * — `XhtmlStateTransferCodec`/`AutocompleteHtmlRenderer` are `milpa/live-web`
 * concerns per the fase-B partition, and get their own coverage there.
 */
final class AutocompleteComponentTest extends TestCase
{
    private function sources(): InMemoryDataSourceRegistry
    {
        $sources = new InMemoryDataSourceRegistry();
        $sources->register(new ArrayDataSource('customers.search', [
            ['value' => 'acme', 'label' => 'Acme Studio', 'search' => 'agency design'],
            ['value' => 'milpa', 'label' => 'Milpa Labs', 'search' => 'framework components'],
            ['value' => 'northwind', 'label' => 'Northwind', 'search' => 'commerce'],
        ]));

        return $sources;
    }

    public function testMountBuildsInitialState(): void
    {
        $component = new AutocompleteComponent($this->sources());
        $context = new ComponentContext(componentId: 'customer-picker', principal: 'user:1', route: '/lab/autocomplete');

        $state = $component->mount([
            'name' => 'customer',
            'label' => 'Customer',
            'source' => 'customers.search',
            'multiple' => true,
            'persistKey' => 'demo.customer',
        ], $context);

        self::assertSame('autocomplete', $state->componentName);
        self::assertSame('customers.search', $state->meta['source']);
        self::assertTrue($state->meta['multiple']);
        self::assertSame([], $state->data['selected']);
    }

    public function testSearchActionMatchesAgainstTheDataSource(): void
    {
        $component = new AutocompleteComponent($this->sources());
        $context = new ComponentContext(componentId: 'customer-picker', route: '/lab/autocomplete');
        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search'], $context);

        $search = $component->handle(new InteractionRequest(
            componentId: 'customer-picker',
            componentName: 'autocomplete',
            action: 'search',
            state: $state,
            payload: ['query' => 'mil'],
        ));

        self::assertSame([], $search->errors);
        self::assertCount(1, $search->state->data['items']);
        self::assertSame('milpa', $search->state->data['items'][0]['value']);
    }

    public function testSelectAccumulatesAndIgnoresDuplicatesWhenMultiple(): void
    {
        $component = new AutocompleteComponent($this->sources());
        $context = new ComponentContext(componentId: 'customer-picker', route: '/lab/autocomplete');
        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search', 'multiple' => true], $context);

        $selectAcme = $component->handle(new InteractionRequest(
            componentId: 'customer-picker',
            componentName: 'autocomplete',
            action: 'select',
            state: $state,
            payload: ['item' => ['value' => 'acme', 'label' => 'Acme Studio']],
        ));
        $selectMilpa = $component->handle(new InteractionRequest(
            componentId: 'customer-picker',
            componentName: 'autocomplete',
            action: 'select',
            state: $selectAcme->state,
            payload: ['item' => ['value' => 'milpa', 'label' => 'Milpa Labs']],
        ));
        $selectDuplicate = $component->handle(new InteractionRequest(
            componentId: 'customer-picker',
            componentName: 'autocomplete',
            action: 'select',
            state: $selectMilpa->state,
            payload: ['item' => ['value' => 'milpa', 'label' => 'Milpa Labs']],
        ));

        self::assertCount(2, $selectMilpa->state->data['selected']);
        self::assertCount(2, $selectDuplicate->state->data['selected'], 'Expected duplicate select to be ignored.');
    }

    public function testRemoveDropsOnlyTheGivenItem(): void
    {
        $component = new AutocompleteComponent($this->sources());
        $context = new ComponentContext(componentId: 'customer-picker', route: '/lab/autocomplete');
        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search', 'multiple' => true], $context);

        $selectAcme = $component->handle(new InteractionRequest(
            componentId: 'customer-picker',
            componentName: 'autocomplete',
            action: 'select',
            state: $state,
            payload: ['item' => ['value' => 'acme', 'label' => 'Acme Studio']],
        ));
        $selectMilpa = $component->handle(new InteractionRequest(
            componentId: 'customer-picker',
            componentName: 'autocomplete',
            action: 'select',
            state: $selectAcme->state,
            payload: ['item' => ['value' => 'milpa', 'label' => 'Milpa Labs']],
        ));

        $removeAcme = $component->handle(new InteractionRequest(
            componentId: 'customer-picker',
            componentName: 'autocomplete',
            action: 'remove',
            state: $selectMilpa->state,
            payload: ['item' => ['value' => 'acme', 'label' => 'Acme Studio']],
        ));

        self::assertCount(1, $removeAcme->state->data['selected']);
        self::assertSame('milpa', $removeAcme->state->data['selected'][0]['value']);
    }

    public function testClearResetsQuerySelectionAndItems(): void
    {
        $component = new AutocompleteComponent($this->sources());
        $context = new ComponentContext(componentId: 'customer-picker', route: '/lab/autocomplete');
        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search'], $context);

        $searched = $component->handle(new InteractionRequest(
            componentId: 'customer-picker',
            componentName: 'autocomplete',
            action: 'search',
            state: $state,
            payload: ['query' => 'mil'],
        ));

        $cleared = $component->handle(new InteractionRequest(
            componentId: 'customer-picker',
            componentName: 'autocomplete',
            action: 'clear',
            state: $searched->state,
        ));

        self::assertSame('', $cleared->state->data['query']);
        self::assertSame([], $cleared->state->data['selected']);
        self::assertSame([], $cleared->state->data['items']);
    }

    public function testUnknownActionReportsAnErrorInsteadOfThrowing(): void
    {
        $component = new AutocompleteComponent($this->sources());
        $context = new ComponentContext(componentId: 'customer-picker', route: '/lab/autocomplete');
        $state = $component->mount(['name' => 'customer', 'source' => 'customers.search'], $context);

        $result = $component->handle(new InteractionRequest(
            componentId: 'customer-picker',
            componentName: 'autocomplete',
            action: 'drop-database',
            state: $state,
        ));

        self::assertArrayHasKey('action', $result->errors);
        self::assertSame($state, $result->state);
    }

    public function testMountRequiresANonEmptySourceProp(): void
    {
        $component = new AutocompleteComponent($this->sources());
        $context = new ComponentContext(componentId: 'customer-picker', route: '/lab/autocomplete');

        $this->expectException(\InvalidArgumentException::class);
        $component->mount(['name' => 'customer'], $context);
    }
}
