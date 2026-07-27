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

use Milpa\Live\Components\Dashboard\DataTableComponent;
use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The table's own state machine — selection, sort and paging.
 *
 * This is the biggest component the package publishes and nothing had ever
 * mounted it. Its renderers in `live-web` and `live-tui` are well covered,
 * which is the trap: painting the state correctly says nothing about whether
 * the state was right.
 */
#[CoversClass(DataTableComponent::class)]
final class DataTableComponentTest extends TestCase
{
    /**
     * @param array<string, mixed> $props
     */
    private function mount(array $props = []): StateSnapshot
    {
        return (new DataTableComponent())->mount(
            array_merge(['name' => 'clientes'], $props),
            new ComponentContext('tabla-1', route: '/clientes', principal: 'user:1'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function act(StateSnapshot $state, string $action, array $payload = []): InteractionResult
    {
        return (new DataTableComponent())->handle(new InteractionRequest(
            componentId: $state->componentId,
            componentName: $state->componentName,
            action: $action,
            state: $state,
            payload: $payload,
        ));
    }

    // ---- the contract ----------------------------------------------------------

    public function testTheContractDeclaresEveryActionTheComponentAnswers(): void
    {
        // A host builds its UI from this. An action that works but is not
        // declared is an action nobody knows to offer.
        $contract = DataTableComponent::contract();

        self::assertSame('data-table', $contract->name);
        foreach (['toggle-row', 'select-row', 'unselect-row', 'clear-selection', 'sort', 'page'] as $action) {
            self::assertArrayHasKey($action, $contract->actions);
        }
    }

    // ---- mounting -----------------------------------------------------------------

    public function testMountingWithNothingButANameGivesAnEmptyButUsableTable(): void
    {
        $state = $this->mount();

        self::assertSame('data-table', $state->componentName);
        self::assertSame([], $state->data['selectedRows']);
        self::assertSame('', $state->data['sortBy']);
        self::assertSame('asc', $state->data['sortDirection']);
        self::assertSame(1, $state->data['page']);
        self::assertNull($state->data['error']);
        self::assertSame([], $state->meta['columns']);
        self::assertSame([], $state->meta['rows']);
        self::assertFalse($state->meta['selectable']);
        self::assertNull($state->meta['persistKey']);
        self::assertSame('local', $state->meta['storage']);
        self::assertSame('/clientes', $state->meta['route']);
    }

    public function testMountingCarriesTheColumnsAndRowsItWasGiven(): void
    {
        $state = $this->mount([
            'caption' => 'Clientes',
            'columns' => [['key' => 'nombre', 'label' => 'Nombre', 'align' => 'right'], 'no soy una columna'],
            'rows' => [['id' => '1', 'nombre' => 'Ana'], 'no soy una fila'],
            'selectable' => true,
            'persistKey' => 'clientes.tabla',
        ]);

        self::assertSame('Clientes', $state->meta['caption']);
        self::assertCount(1, $state->meta['columns'], 'Junk among the columns is dropped, not rendered.');
        self::assertSame(['key' => 'nombre', 'label' => 'Nombre', 'align' => 'right'], $state->meta['columns'][0]);
        self::assertCount(1, $state->meta['rows']);
        self::assertTrue($state->meta['selectable']);
        self::assertSame('clientes.tabla', $state->meta['persistKey']);
    }

    public function testAColumnFillsInWhatItDoesNotDeclare(): void
    {
        // A half-specified column comes from a host template, not from here.
        // Left alignment and the key as its own label are the safe defaults.
        $state = $this->mount(['columns' => ['nombre' => []]]);

        self::assertSame(['key' => 'nombre', 'label' => 'nombre', 'align' => 'left'], $state->meta['columns'][0]);
    }

    public function testColumnsAndRowsArrivingAsJsonStringsAreDecoded(): void
    {
        // An HTML attribute can only carry a string. Without the decode a table
        // configured from markup would mount with no columns at all.
        $state = $this->mount([
            'columns' => '[{"key":"nombre","label":"Nombre"}]',
            'rows' => '[{"id":"1"}]',
        ]);

        self::assertSame('nombre', $state->meta['columns'][0]['key']);
        self::assertCount(1, $state->meta['rows']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function junk(): iterable
    {
        yield 'a string that is not json' => ['no soy json'];
        yield 'json that is not a list' => ['"soy una cadena"'];
        yield 'a number' => [42];
        yield 'null' => [null];
        yield 'a boolean' => [true];
    }

    #[DataProvider('junk')]
    public function testJunkWhereColumnsOrRowsWereExpectedMountsEmptyRatherThanFailing(mixed $value): void
    {
        $state = $this->mount(['columns' => $value, 'rows' => $value]);

        self::assertSame([], $state->meta['columns']);
        self::assertSame([], $state->meta['rows']);
    }

    public function testAPageBelowTheFirstIsClampedAtMount(): void
    {
        self::assertSame(1, $this->mount(['page' => -3])->data['page']);
    }

    public function testAPreselectionIsNormalisedToStringsAndEmptiesAreDropped(): void
    {
        // Row ids arrive from JSON, where a numeric id is a number. Comparing
        // them against string ids later would never match.
        $state = $this->mount(['selectedRows' => [1, '2', null, '', 3]]);

        self::assertSame(['1', '2', '3'], $state->data['selectedRows']);
    }

    // ---- selection --------------------------------------------------------------------

    public function testTogglingARowAddsItAndTogglingAgainRemovesIt(): void
    {
        $state = $this->mount();

        $afterFirst = $this->act($state, 'toggle-row', ['rowId' => 'r1'])->state;
        self::assertSame(['r1'], $afterFirst->data['selectedRows']);

        $afterSecond = $this->act($afterFirst, 'toggle-row', ['rowId' => 'r1'])->state;
        self::assertSame([], $afterSecond->data['selectedRows']);
    }

    public function testSelectingARowTwiceStillSelectsItOnce(): void
    {
        // A double click, or two clients acting on the same state, must not
        // make one row count as two in whatever consumes the selection.
        $state = $this->act($this->mount(), 'select-row', ['rowId' => 'r1'])->state;
        $state = $this->act($state, 'select-row', ['rowId' => 'r1'])->state;

        self::assertSame(['r1'], $state->data['selectedRows']);
    }

    public function testUnselectingARowThatWasNotSelectedChangesNothing(): void
    {
        $state = $this->act($this->mount(['selectedRows' => ['r1']]), 'unselect-row', ['rowId' => 'r9'])->state;

        self::assertSame(['r1'], $state->data['selectedRows']);
    }

    public function testUnselectingLeavesTheOtherRowsAlone(): void
    {
        $state = $this->act($this->mount(['selectedRows' => ['r1', 'r2', 'r3']]), 'unselect-row', ['rowId' => 'r2'])->state;

        self::assertSame(['r1', 'r3'], $state->data['selectedRows']);
    }

    public function testClearingDropsTheWholeSelectionAtOnce(): void
    {
        $result = $this->act($this->mount(['selectedRows' => ['r1', 'r2']]), 'clear-selection');

        self::assertSame([], $result->state->data['selectedRows']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function rowActions(): iterable
    {
        yield 'toggle-row' => ['toggle-row'];
        yield 'select-row' => ['select-row'];
    }

    #[DataProvider('rowActions')]
    public function testARowActionWithNoRowIdIsRefusedByName(string $action): void
    {
        // Acting on "" would select a row that does not exist and leave the
        // selection quietly wrong.
        $result = $this->act($this->mount(), $action);

        self::assertArrayHasKey('rowId', $result->errors);
        self::assertSame([], $result->state->data['selectedRows']);
    }

    public function testAnEmptySelectionAsksToBeForgottenAndANonEmptyOneToBePersisted(): void
    {
        // The effect is what tells the client to write or clear its storage.
        // Persisting an empty selection would leave a stale row highlighted on
        // the next visit.
        $conSeleccion = $this->act($this->mount(), 'select-row', ['rowId' => 'r1']);
        $sinSeleccion = $this->act($this->mount(['selectedRows' => ['r1']]), 'clear-selection');

        self::assertSame([['type' => 'persist']], $conSeleccion->effects);
        self::assertSame([['type' => 'forget']], $sinSeleccion->effects);
    }

    public function testASelectionActionClearsAnyPreviousError(): void
    {
        $state = new StateSnapshot('tabla-1', 'data-table', '0.6.0-candidate', ['selectedRows' => [], 'error' => 'algo falló'], []);

        self::assertNull($this->act($state, 'select-row', ['rowId' => 'r1'])->state->data['error']);
    }

    // ---- sorting -------------------------------------------------------------------------

    public function testSortingByANewColumnStartsAscending(): void
    {
        $result = $this->act($this->mount(), 'sort', ['key' => 'nombre']);

        self::assertSame('nombre', $result->state->data['sortBy']);
        self::assertSame('asc', $result->state->data['sortDirection']);
    }

    public function testSortingAgainByTheSameColumnFlipsTheDirection(): void
    {
        $state = $this->act($this->mount(), 'sort', ['key' => 'nombre'])->state;

        $flipped = $this->act($state, 'sort', ['key' => 'nombre'])->state;
        self::assertSame('desc', $flipped->data['sortDirection']);

        $back = $this->act($flipped, 'sort', ['key' => 'nombre'])->state;
        self::assertSame('asc', $back->data['sortDirection'], 'And a third click comes back around.');
    }

    public function testSortingByADifferentColumnStartsAscendingAgain(): void
    {
        // Carrying the previous column's direction over is the classic table
        // bug: the user clicks a fresh header and gets it sorted backwards.
        $state = $this->act($this->mount(), 'sort', ['key' => 'nombre'])->state;
        $state = $this->act($state, 'sort', ['key' => 'nombre'])->state;

        $otra = $this->act($state, 'sort', ['key' => 'plan'])->state;

        self::assertSame('plan', $otra->data['sortBy']);
        self::assertSame('asc', $otra->data['sortDirection']);
    }

    // ---- paging ---------------------------------------------------------------------------

    public function testPagingMovesToThePageItWasGiven(): void
    {
        self::assertSame(4, $this->act($this->mount(), 'page', ['page' => 4])->state->data['page']);
    }

    public function testPagingBelowTheFirstPageIsClamped(): void
    {
        self::assertSame(1, $this->act($this->mount(), 'page', ['page' => 0])->state->data['page']);
        self::assertSame(1, $this->act($this->mount(), 'page', ['page' => -7])->state->data['page']);
    }

    public function testPagingWithNoPageAtAllLandsOnTheFirst(): void
    {
        self::assertSame(1, $this->act($this->mount(), 'page')->state->data['page']);
    }

    public function testPagingKeepsTheSelectionAndTheSort(): void
    {
        // Losing the selection on page change is how a user selects three rows,
        // pages forward to find a fourth, and comes back to nothing.
        $state = $this->mount(['selectedRows' => ['r1']]);
        $state = $this->act($state, 'sort', ['key' => 'nombre'])->state;

        $paged = $this->act($state, 'page', ['page' => 2])->state;

        self::assertSame(['r1'], $paged->data['selectedRows']);
        self::assertSame('nombre', $paged->data['sortBy']);
    }

    // ---- anything else ----------------------------------------------------------------------

    public function testAnActionTheTableDoesNotKnowIsNamedInTheError(): void
    {
        $result = $this->act($this->mount(), 'drop-table');

        self::assertArrayHasKey('action', $result->errors);
        self::assertStringContainsString('drop-table', $result->errors['action']);
    }

    public function testAnUnknownActionLeavesTheStateExactlyAsItWas(): void
    {
        $state = $this->mount(['selectedRows' => ['r1']]);

        $result = $this->act($state, 'drop-table');

        self::assertSame($state, $result->state);
    }

    public function testEveryActionKeepsTheMountTimeMetaItWasNotAskedToChange(): void
    {
        // Columns and rows are mount-time facts; an action that rewrote them
        // would empty the table as a side effect of clicking a row.
        $state = $this->mount([
            'columns' => [['key' => 'nombre', 'label' => 'Nombre']],
            'rows' => [['id' => '1']],
        ]);

        foreach ([['toggle-row', ['rowId' => 'r1']], ['sort', ['key' => 'n']], ['page', ['page' => 2]], ['clear-selection', []]] as [$action, $payload]) {
            $after = $this->act($state, $action, $payload)->state;

            self::assertSame($state->meta, $after->meta, "The meta survived {$action}.");
        }
    }
}
