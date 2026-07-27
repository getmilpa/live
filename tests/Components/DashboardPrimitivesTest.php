<?php

/**
 * This file is part of Milpa Live — the live component core of the Milpa PHP framework.
 *
 * (c) Rodrigo Vicente - TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

namespace Milpa\Live\Tests\Components;

use Milpa\Live\Components\Dashboard\AbstractDashboardComponent;
use Milpa\Live\Components\Dashboard\DashboardActionButtonComponent;
use Milpa\Live\Components\Dashboard\DashboardAlertListComponent;
use Milpa\Live\Components\Dashboard\DashboardGridComponent;
use Milpa\Live\Components\Dashboard\DashboardMainComponent;
use Milpa\Live\Components\Dashboard\DashboardPageHeaderComponent;
use Milpa\Live\Components\Dashboard\DashboardPanelComponent;
use Milpa\Live\Components\Dashboard\DashboardShellComponent;
use Milpa\Live\Components\Dashboard\DashboardSidebarComponent;
use Milpa\Live\Components\Dashboard\DashboardTopbarComponent;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\InteractionRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The nine dashboard primitives, none of which had ever been mounted here.
 *
 * They carry almost no behaviour on purpose — the point of each is the meta it
 * hands its renderer. Which is exactly why an unmounted one is dangerous: a
 * default that changed, or a prop read under the wrong key, shows up as an
 * empty panel in someone's dashboard and nowhere else.
 */
#[CoversClass(AbstractDashboardComponent::class)]
#[CoversClass(DashboardShellComponent::class)]
#[CoversClass(DashboardMainComponent::class)]
#[CoversClass(DashboardGridComponent::class)]
#[CoversClass(DashboardPanelComponent::class)]
#[CoversClass(DashboardTopbarComponent::class)]
#[CoversClass(DashboardPageHeaderComponent::class)]
#[CoversClass(DashboardSidebarComponent::class)]
#[CoversClass(DashboardAlertListComponent::class)]
#[CoversClass(DashboardActionButtonComponent::class)]
final class DashboardPrimitivesTest extends TestCase
{
    private function context(): ComponentContext
    {
        return new ComponentContext('primitivo-1', route: '/panel', principal: 'user:1');
    }

    /**
     * Every dashboard primitive, so the shared plumbing is proven on all of
     * them rather than on whichever one happens to be tested.
     *
     * @return iterable<string, array{AbstractDashboardComponent, string}>
     */
    public static function primitives(): iterable
    {
        yield 'shell' => [new DashboardShellComponent(), 'dashboard-shell'];
        yield 'main' => [new DashboardMainComponent(), 'dashboard-main'];
        yield 'grid' => [new DashboardGridComponent(), 'dashboard-grid'];
        yield 'panel' => [new DashboardPanelComponent(), 'dashboard-panel'];
        yield 'topbar' => [new DashboardTopbarComponent(), 'dashboard-topbar'];
        yield 'page header' => [new DashboardPageHeaderComponent(), 'dashboard-page-header'];
        yield 'sidebar' => [new DashboardSidebarComponent(), 'dashboard-sidebar'];
        yield 'alert list' => [new DashboardAlertListComponent(), 'dashboard-alert-list'];
        yield 'action button' => [new DashboardActionButtonComponent(), 'dashboard-action-button'];
    }

    // ---- the shared plumbing -----------------------------------------------------

    #[DataProvider('primitives')]
    public function testEveryPrimitiveMountsUnderItsOwnContractName(AbstractDashboardComponent $component, string $name): void
    {
        $state = $component->mount([], $this->context());

        self::assertSame($name, $state->componentName);
        self::assertSame($name, $component::contract()->name);
        self::assertNotSame('', $component::contract()->contractVersion);
    }

    #[DataProvider('primitives')]
    public function testEveryPrimitiveMountsReadyAndCarriesItsContext(AbstractDashboardComponent $component, string $name): void
    {
        $state = $component->mount([], $this->context());

        self::assertTrue($state->data['ready']);
        self::assertSame('primitivo-1', $state->meta['id'], 'With no id prop, the instance id stands in.');
        self::assertSame('', $state->meta['title']);
        self::assertSame('/panel', $state->meta['route']);
        self::assertSame('user:1', $state->meta['principal']);
    }

    #[DataProvider('primitives')]
    public function testAnExplicitIdAndTitleWinOverTheDefaults(AbstractDashboardComponent $component, string $name): void
    {
        $state = $component->mount(['id' => 'mio', 'title' => 'Mi panel'], $this->context());

        self::assertSame('mio', $state->meta['id']);
        self::assertSame('Mi panel', $state->meta['title']);
    }

    #[DataProvider('primitives')]
    public function testNoPrimitiveHandlesAnyActionAndSaysSoByName(AbstractDashboardComponent $component, string $name): void
    {
        // These are layout primitives; they have no actions on purpose. The
        // error is what stops a host from wiring a click to nothing and
        // wondering why it does not work.
        $state = $component->mount([], $this->context());

        $result = $component->handle(new InteractionRequest(
            componentId: $state->componentId,
            componentName: $state->componentName,
            action: 'clic',
            state: $state,
            payload: [],
        ));

        self::assertArrayHasKey('action', $result->errors);
        self::assertStringContainsString('clic', $result->errors['action']);
        self::assertSame($state, $result->state, 'And the state is handed back untouched.');
    }

    // ---- what each one adds of its own ----------------------------------------------

    public function testTheShellDefaultsToComfortableDensity(): void
    {
        self::assertSame('comfortable', (new DashboardShellComponent())->mount([], $this->context())->meta['density']);
        self::assertSame('compact', (new DashboardShellComponent())->mount(['density' => 'compact'], $this->context())->meta['density']);
    }

    public function testTheGridDefaultsToThreeColumnsAndClampsToSix(): void
    {
        // A grid of 0 columns cannot be laid out and a grid of 40 is not a
        // grid. The clamp is what keeps a typo in a template from breaking the
        // page instead of just looking odd.
        $grid = new DashboardGridComponent();

        self::assertSame(3, $grid->mount([], $this->context())->meta['columns']);
        self::assertSame(4, $grid->mount(['columns' => 4], $this->context())->meta['columns']);
        self::assertSame(1, $grid->mount(['columns' => 0], $this->context())->meta['columns']);
        self::assertSame(1, $grid->mount(['columns' => -5], $this->context())->meta['columns']);
        self::assertSame(6, $grid->mount(['columns' => 40], $this->context())->meta['columns']);
        self::assertSame('md', $grid->mount([], $this->context())->meta['gap']);
    }

    public function testAPanelSpansOneColumnByDefaultAndIsClampedToSix(): void
    {
        $panel = new DashboardPanelComponent();

        self::assertSame(1, $panel->mount([], $this->context())->meta['span']);
        self::assertSame(6, $panel->mount(['span' => 99], $this->context())->meta['span']);
        self::assertSame(1, $panel->mount(['span' => 0], $this->context())->meta['span']);
        self::assertSame('default', $panel->mount([], $this->context())->meta['tone']);
        self::assertSame('Lo de esta semana', $panel->mount(['description' => 'Lo de esta semana'], $this->context())->meta['description']);
    }

    public function testTheTopbarCarriesItsFourOptionalStrings(): void
    {
        $state = (new DashboardTopbarComponent())->mount([
            'subtitle' => 'Todos',
            'eyebrow' => 'Panel',
            'controls' => '<button>x</button>',
            'searchPlaceholder' => 'Buscar cliente…',
        ], $this->context());

        self::assertSame('Todos', $state->meta['subtitle']);
        self::assertSame('Panel', $state->meta['eyebrow']);
        self::assertSame('<button>x</button>', $state->meta['controls']);
        self::assertSame('Buscar cliente…', $state->meta['searchPlaceholder']);
    }

    public function testThePageHeaderCarriesItsEyebrowAndDescription(): void
    {
        $state = (new DashboardPageHeaderComponent())->mount(
            ['eyebrow' => 'Panel', 'description' => 'Todos los activos'],
            $this->context(),
        );

        self::assertSame('Panel', $state->meta['eyebrow']);
        self::assertSame('Todos los activos', $state->meta['description']);
    }

    public function testTheActionButtonDefaultsToAGhostButton(): void
    {
        $button = new DashboardActionButtonComponent();

        $porDefecto = $button->mount(['label' => 'Guardar'], $this->context())->meta;
        self::assertSame('Guardar', $porDefecto['label']);
        self::assertSame('ghost', $porDefecto['variant']);
        self::assertSame('sm', $porDefecto['size']);
        self::assertSame('button', $porDefecto['type'], 'Not submit: a button inside a form would post it by accident.');

        $explicito = $button->mount(['label' => 'Enviar', 'variant' => 'primary', 'size' => 'lg', 'type' => 'submit'], $this->context())->meta;
        self::assertSame('primary', $explicito['variant']);
        self::assertSame('submit', $explicito['type']);
    }

    // ---- the sidebar's navigation --------------------------------------------------------

    public function testTheSidebarBrandsItselfMilpaUntilToldOtherwise(): void
    {
        $state = (new DashboardSidebarComponent())->mount([], $this->context());

        self::assertSame('Milpa', $state->meta['brand']);
        self::assertSame('', $state->meta['active']);
        self::assertSame([], $state->meta['items']);
    }

    public function testSidebarItemsFillInWhatTheyDoNotDeclare(): void
    {
        // A nav item with no href would render as a dead link; '#' at least
        // stays on the page instead of navigating to nowhere.
        $state = (new DashboardSidebarComponent())->mount([
            'items' => [
                ['key' => 'inicio', 'label' => 'Inicio', 'href' => '/'],
                'clientes' => [],
                'no soy un elemento',
            ],
        ], $this->context());

        self::assertCount(2, $state->meta['items'], 'The string was dropped, not rendered.');
        self::assertSame(['key' => 'inicio', 'label' => 'Inicio', 'href' => '/'], $state->meta['items'][0]);
        self::assertSame(['key' => 'clientes', 'label' => 'clientes', 'href' => '#'], $state->meta['items'][1]);
    }

    public function testSidebarItemsArrivingAsAJsonStringAreDecoded(): void
    {
        // An HTML attribute can only carry a string. Without the decode a
        // sidebar configured from markup would mount with no navigation.
        $state = (new DashboardSidebarComponent())->mount(
            ['items' => '[{"key":"inicio","label":"Inicio"}]', 'active' => 'inicio'],
            $this->context(),
        );

        self::assertSame('inicio', $state->meta['items'][0]['key']);
        self::assertSame('inicio', $state->meta['active']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function junkItems(): iterable
    {
        yield 'a string that is not json' => ['no soy json'];
        yield 'a number' => [42];
        yield 'null' => [null];
        yield 'a boolean' => [false];
    }

    #[DataProvider('junkItems')]
    public function testJunkWhereSidebarItemsWereExpectedMountsWithNoNavigation(mixed $value): void
    {
        $state = (new DashboardSidebarComponent())->mount(['items' => $value], $this->context());

        self::assertSame([], $state->meta['items']);
    }

    // ---- the alert list ----------------------------------------------------------------------

    public function testAlertsCarryTheirCountAndTheirTextAsStrings(): void
    {
        // The count arrives from JSON as a number and is rendered beside the
        // text; keeping it typed would make one alert format differently from
        // the next for no visible reason.
        $state = (new DashboardAlertListComponent())->mount([
            'items' => [
                ['count' => 3, 'text' => 'facturas vencidas'],
                ['text' => 'sin contador'],
                'no soy una alerta',
            ],
        ], $this->context());

        self::assertCount(2, $state->meta['items']);
        self::assertSame(['count' => '3', 'text' => 'facturas vencidas'], $state->meta['items'][0]);
        self::assertSame(['count' => '', 'text' => 'sin contador'], $state->meta['items'][1]);
    }

    public function testAnAlertListWithJunkInsteadOfItemsMountsEmpty(): void
    {
        $state = (new DashboardAlertListComponent())->mount(['items' => 'ninguna'], $this->context());

        self::assertSame([], $state->meta['items']);
    }
}
