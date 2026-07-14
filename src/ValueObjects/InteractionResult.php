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

namespace Milpa\Live\ValueObjects;

/**
 * The outcome of {@see \Milpa\Live\Contracts\Component\ComponentDefinitionInterface::handle()}
 * processing an {@see InteractionRequest}. `$state` is always the
 * component's new state — even an action that ultimately failed MUST
 * return the (possibly unchanged) state rather than omitting it, since
 * callers do not treat a non-empty `$errors` as "no state to persist".
 */
final readonly class InteractionResult
{
    /**
     * @param StateSnapshot                    $state   The component's state after the action was applied.
     * @param string|null                      $html    Pre-rendered HTML for this result, when the component renders itself
     *                                                  rather than leaving re-rendering to a {@see \Milpa\Live\Contracts\Rendering\ComponentRendererInterface}.
     * @param array<int, array<string, mixed>> $effects Client-side side effects to perform (e.g. `['type' => 'persist']`);
     *                                                  shape is action/effect-type defined, not standardized here.
     * @param array<string, mixed>             $errors  Field/reason => human-readable message, for an action the component
     *                                                  recognized but rejected or partially failed.
     * @param array<string, mixed>             $meta    Caller-defined extra context beyond the fields above.
     */
    public function __construct(
        public StateSnapshot $state,
        public ?string $html = null,
        public array $effects = [],
        public array $errors = [],
        public array $meta = [],
    ) {
    }
}
