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

namespace Milpa\Live\Events;

use Milpa\Interfaces\Event\EventDeclaration;

/**
 * Every event this package dispatches, by name and as a declaration — the
 * one holder the house reads to answer «what events does milpa/live emit?»
 * without scanning source (greenhouse decisions/0228).
 *
 * The constants ARE the names {@see LiveEventEmitter} hands to `dispatch()`:
 * a declaration and its dispatch share the same constant, never a retyped
 * copy of the string, so a renamed event cannot drift from its declaration.
 * `live.request`/`live.responded` are declared here, not in `milpa/live-web`,
 * because their `dispatch()` sites are in this package's emitter — the
 * endpoint that calls them holds no dispatch site of its own.
 */
final class LiveEvents
{
    /** PRE: a component is about to be mounted (no slot). */
    public const COMPONENT_MOUNTING = 'component.mounting';

    /** POST: a component was mounted and its initial state exists. */
    public const COMPONENT_MOUNTED = 'component.mounted';

    /** PRE: a component is about to handle an interaction (slot: short-circuit or veto). */
    public const COMPONENT_HANDLING = 'component.handling';

    /** POST: an interaction was handled, by the component or by a listener. */
    public const COMPONENT_HANDLED = 'component.handled';

    /** PRE: a component is about to render for a target (slot: short-circuit or veto). */
    public const COMPONENT_RENDERING = 'component.rendering';

    /** POST: a component rendered for a target, or a listener answered in its place. */
    public const COMPONENT_RENDERED = 'component.rendered';

    /** PRE: a live HTTP interaction passed every gate and is about to be handled (slot). */
    public const LIVE_REQUEST = 'live.request';

    /** POST: a live HTTP interaction was answered. */
    public const LIVE_RESPONDED = 'live.responded';

    private function __construct()
    {
        // Static-only holder — never instantiated.
    }

    /**
     * One declaration per event name this package dispatches, in lifecycle order.
     *
     * Every payload carries its subject under `event` (the framework's
     * `'event' => VO` convention); the PRE events with an interception seam
     * also carry an `InterceptionSlot` under `slot`, which is what
     * `interceptable` says. No subject is mutable: every event VO is readonly.
     *
     * @return list<EventDeclaration>
     */
    public static function declarations(): array
    {
        return [
            new EventDeclaration(
                name: self::COMPONENT_MOUNTING,
                dispatchedBy: LiveEventEmitter::class,
                when: 'A component is about to be mounted, before mount() computes its initial state.',
                subjectKey: 'event',
                subjectType: ComponentMountingEvent::class,
            ),
            new EventDeclaration(
                name: self::COMPONENT_MOUNTED,
                dispatchedBy: LiveEventEmitter::class,
                when: 'A component was mounted and its initial state snapshot exists.',
                subjectKey: 'event',
                subjectType: ComponentMountedEvent::class,
            ),
            new EventDeclaration(
                name: self::COMPONENT_HANDLING,
                dispatchedBy: LiveEventEmitter::class,
                when: 'A component is about to handle an interaction; a subscriber may short-circuit with an InteractionResult or veto it.',
                subjectKey: 'event',
                subjectType: ComponentHandlingEvent::class,
                interceptable: true,
            ),
            new EventDeclaration(
                name: self::COMPONENT_HANDLED,
                dispatchedBy: LiveEventEmitter::class,
                when: 'An interaction was handled, by the component itself or by a subscriber that intercepted it.',
                subjectKey: 'event',
                subjectType: ComponentHandledEvent::class,
            ),
            new EventDeclaration(
                name: self::COMPONENT_RENDERING,
                dispatchedBy: LiveEventEmitter::class,
                when: 'A component is about to render for a target; a subscriber may short-circuit with a RenderResult or veto it.',
                subjectKey: 'event',
                subjectType: ComponentRenderingEvent::class,
                interceptable: true,
            ),
            new EventDeclaration(
                name: self::COMPONENT_RENDERED,
                dispatchedBy: LiveEventEmitter::class,
                when: 'A component rendered for a target, or a subscriber answered in its place.',
                subjectKey: 'event',
                subjectType: ComponentRenderedEvent::class,
            ),
            new EventDeclaration(
                name: self::LIVE_REQUEST,
                dispatchedBy: LiveEventEmitter::class,
                when: 'A live HTTP interaction passed every security gate and is about to reach the component; a subscriber may short-circuit or veto it.',
                subjectKey: 'event',
                subjectType: LiveRequestEvent::class,
                interceptable: true,
            ),
            new EventDeclaration(
                name: self::LIVE_RESPONDED,
                dispatchedBy: LiveEventEmitter::class,
                when: 'A live HTTP interaction was answered, whether by the component or by an intercepting subscriber.',
                subjectKey: 'event',
                subjectType: LiveRespondedEvent::class,
            ),
        ];
    }
}
