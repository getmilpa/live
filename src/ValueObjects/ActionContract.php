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

use Milpa\Command\Effect\EffectProfile;
use Milpa\Command\Effect\Mutation;

/**
 * What a component ACTION is for, not only what its payload looks like.
 *
 * Until this existed, `ComponentContract::$actions` was action name → payload shape: a button that saves and
 * a button that toggles a disclosure were indistinguishable to anything reading the contract, including the
 * agent. This says what the action DOES, in the vocabulary that already governs Operations — no second one
 * (greenhouse decisions/0214 point 2).
 *
 * ── `null` MEANS «NOT DECLARED», AND THAT IS LOAD-BEARING ───────────────────────────────────────────
 *
 * The words are the same as an Operation's but the DEFAULT is deliberately the opposite, and mixing them up
 * would invert this contract's whole promise. For an Operation, silence about effects means the CEILING:
 * `Operation::effectCeiling()` answers `EffectProfile::unclassified()`, which admits nothing. For a component
 * action, silence means «nobody said», and the house keeps behaving exactly as it did.
 *
 * So {@see $effects} is `?EffectProfile` and its absence is `null` — never `unclassified()`. If it ever
 * defaulted to a profile, every existing undeclared button in every app would become maximally suspicious
 * overnight, and an additive change would have become a breaking one. Anything that gates on this must ask
 * «was it declared?» BEFORE it asks «what does the profile say?».
 *
 * ── AN ACTION THAT DELEGATES NAMES ITS OPERATION, IT DOES NOT COPY IT ──────────────────────────────
 *
 * Most component actions are their own act. Some are a BUTTON FOR AN OPERATION — a panel offering to install a
 * capability is offering `capabilities:enable`, which already declares that it downloads third-party code, that
 * its authority is privileged and that its reversibility is manual recovery.
 *
 * Restating that profile here would be two sources of truth about one act, and they would drift the first time
 * the operation's own classification changed. {@see $invokes} names the operation instead: the action says WHAT
 * IT RUNS, and the profile stays where it was declared and where the gate already reads it.
 *
 * ── WHAT IT REFUSES ────────────────────────────────────────────────────────────────────────────────
 *
 * A declaration that contradicts itself is refused where it is written, not obeyed and puzzled over later:
 * `mutating: false` beside a profile that mutates, or `mutating: true` beside `Mutation::None`. Saying both
 * is not a nuance — one of the two is wrong, and the author is the only one who knows which.
 */
final readonly class ActionContract
{
    /**
     * @param string               $summary     What the action is for, in one sentence — the text an agent's
     *                                          catalogue shows. There is no second copy to keep in sync.
     * @param bool                 $mutating    Whether performing it changes anything.
     * @param EffectProfile|null   $effects     The five-axis profile, or `null` for NOT DECLARED. Never a
     *                                          default profile: see the note above.
     * @param string|null          $namedTarget The payload field a human must name for this action to be
     *                                          meaningful (ADR-0044), when it has one.
     * @param string|null          $scopeBy     A payload field whose VALUE, not the action name, derives the
     *                                          authorization scope — one generic action carrying per-event
     *                                          authorization (greenhouse decisions/0096).
     * @param array<string, mixed> $payload     The payload shape, exactly as the bare-array form declared it.
     */
    public function __construct(
        public string $summary = '',
        public bool $mutating = false,
        public ?EffectProfile $effects = null,
        public ?string $namedTarget = null,
        public ?string $scopeBy = null,
        public ?string $invokes = null,
        public array $payload = [],
    ) {
        if ($invokes !== null && $effects !== null) {
            throw new \InvalidArgumentException(sprintf(
                'An action that delegates to the operation "%s" cannot also carry its own effect profile. The '
                . 'operation declares its effects and its gate reads them there; a second copy here would drift '
                . 'the first time that declaration changed. Name the operation, or own the effects.',
                $invokes,
            ));
        }

        if ($effects === null) {
            return;
        }

        if (!$mutating && $effects->mutation !== Mutation::None) {
            throw new \InvalidArgumentException(sprintf(
                'An action declared not mutating cannot carry an effect profile whose mutation is "%s". '
                . 'Say which one is true: drop the profile, or declare the action mutating.',
                $effects->mutation->value,
            ));
        }

        if ($mutating && $effects->mutation === Mutation::None) {
            throw new \InvalidArgumentException(
                'An action declared mutating cannot carry an effect profile whose mutation is "none". '
                . 'Say which one is true: drop `mutating`, or give the profile the mutation it really has.',
            );
        }
    }

    /**
     * Whether anything at all was declared about this action's effects.
     *
     * The question every gate must ask FIRST. An undeclared action is not a safe action and it is not a
     * dangerous one — it is one nobody described, and treating «nobody said» as either answer is the mistake
     * this method exists to prevent.
     */
    public function declaresEffects(): bool
    {
        return $this->effects !== null;
    }
}
