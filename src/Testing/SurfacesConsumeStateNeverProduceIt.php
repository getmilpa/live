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

namespace Milpa\Live\Testing;

use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\StateSnapshot;

/**
 * The attempt to break a candidate law, made permanent.
 *
 * The law reads *shells consume state, never produce it*, and it came out of read surfaces: the same
 * provider feeding a web page and a terminal. Reading is where it is easiest to be true. Mutation is
 * where it is worth something — a web form arrives as a POST body, a terminal as keystrokes, an
 * agent as a schema-checked tool call, and each is tempted to assemble the resulting state itself
 * because it already holds the pieces.
 *
 * So this is the falsifier, written before the verdict: **the state a surface ends a mutation with
 * must be byte-identical to what the component alone produces from the same request.** A surface
 * that amends, decorates, reorders or synthesizes anything fails it. Nothing here asks whether the
 * output looks right — only whether the surface stayed a consumer.
 *
 * Deliberately not satisfiable by inspection. A surface passes by handing over what it actually
 * ended up with, at the end of its own real mutation path, and letting it be compared against the
 * component run in isolation. If the two agree, the surface added nothing; if they differ, the law
 * is refuted for that surface and degrades to whatever the evidence still supports.
 *
 * Its own limits, stated so nobody reads more into a green run.
 *
 * It compares end states. A surface that produced state and then discarded it would pass, and so
 * would one whose side effects happen elsewhere. What it forecloses is the failure the law is
 * actually about — the surface deciding what the new state should be.
 *
 * And it will go red on a legitimately intercepted interaction. `milpa/live-web` lets a
 * `live.request` listener answer with the `InteractionResult` outright, so the component never
 * runs and the end state is not one it produced. That is a cache doing its job, and it means the
 * law has two readings that part company here:
 *
 * - *state comes only from the component* — refuted, deliberately, by that design.
 * - *the shell authors nothing; it delegates* — survives, and is what the code implements.
 *
 * This asserts the second. Point it at an intercepted interaction and it fails for a reason that
 * is not a defect, which is worth knowing before the red appears rather than after.
 */
trait SurfacesConsumeStateNeverProduceIt
{
    /**
     * Asserts the surface consumed, and did not produce.
     *
     * @param ComponentDefinitionInterface $component     the definition, run in isolation as the reference
     * @param InteractionRequest           $request       exactly what the surface captured and handed over
     * @param StateSnapshot                $surfaceResult the state the surface ended the mutation with
     */
    protected function assertSurfaceOnlyConsumedState(
        ComponentDefinitionInterface $component,
        InteractionRequest $request,
        StateSnapshot $surfaceResult,
    ): void {
        $alone = $component->handle($request)->state;

        // Compared as data, not by identity: a surface may legitimately carry the snapshot across a
        // wire and rebuild it — that is rehydration, and the rebuilt value is the same value. What
        // it may not do is come back with different contents.
        self::assertSame(
            $this->fingerprintOf($alone),
            $this->fingerprintOf($surfaceResult),
            'The surface ended this mutation with state the component did not produce — it authored '
            . 'some of it. That refutes "shells consume state, never produce it" for this surface.',
        );
    }

    /**
     * Everything about a snapshot that would reveal an amendment, in a stable order.
     *
     * Sorted at every depth because two surfaces serialize in different orders and key order is not
     * part of the state. Comparing raw JSON would fail honest surfaces for a spelling difference and
     * teach whoever hit it that the law is noise.
     */
    private function fingerprintOf(StateSnapshot $state): string
    {
        return (string) json_encode([
            'componentId' => $state->componentId,
            'componentName' => $state->componentName,
            'version' => $state->version,
            'data' => $this->sortDeep($state->data),
            'meta' => $this->sortDeep($state->meta),
        ], \JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>
     */
    private function sortDeep(array $value): array
    {
        if (!array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            if (\is_array($item)) {
                $value[$key] = $this->sortDeep($item);
            }
        }

        return $value;
    }
}
