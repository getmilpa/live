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

namespace Milpa\Live\Contracts\Component;

use Milpa\Live\ValueObjects\ComponentContext;
use Milpa\Live\ValueObjects\ComponentContract;
use Milpa\Live\ValueObjects\InteractionRequest;
use Milpa\Live\ValueObjects\InteractionResult;
use Milpa\Live\ValueObjects\StateSnapshot;

/**
 * A live component: the render-target-agnostic unit this package
 * orchestrates. A definition owns no rendering itself — turning its state
 * into markup/TUI frames is the job of a
 * {@see \Milpa\Live\Contracts\Rendering\ComponentRendererInterface} the
 * caller pairs it with — but it DOES own the component's shape ({@see
 * contract()}), its initial state ({@see mount()}), and how it reacts to
 * client-originated actions ({@see handle()}).
 */
interface ComponentDefinitionInterface
{
    /**
     * The component's machine-readable, render-target-agnostic contract
     * (name, schema, declared actions/data sources). MUST be a pure
     * function of the class — no instance state — since callers use it as
     * a static registry key (see {@see \Milpa\Live\Contracts\Component\ComponentRegistryInterface}).
     */
    public static function contract(): ComponentContract;

    /**
     * Builds the component's initial {@see StateSnapshot} from mount-time
     * props. Called once per component lifecycle, before any
     * {@see handle()} call. A caller that already holds a valid snapshot
     * (e.g. one round-tripped from the client) MAY skip mount() and render
     * that snapshot directly instead of remounting.
     *
     * @param array<string, mixed> $props Mount-time props, as passed by the caller (e.g. template attributes).
     */
    public function mount(array $props, ComponentContext $context): StateSnapshot;

    /**
     * Applies a client-originated action to the component's current state
     * and returns the resulting state plus any side effects. {@see
     * StateSnapshot} is immutable, so implementations MUST return a new
     * snapshot via {@see InteractionResult::$state} rather than mutating
     * {@see InteractionRequest::$state} in place. An unrecognized {@see
     * InteractionRequest::$action} SHOULD be reported via {@see
     * InteractionResult::$errors} rather than thrown, so the caller can
     * surface it to the client instead of failing the request outright.
     */
    public function handle(InteractionRequest $request): InteractionResult;
}
