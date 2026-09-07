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

namespace Milpa\Live\Tests\ValueObjects;

use Milpa\Command\Effect\Authority;
use Milpa\Command\Effect\EffectProfile;
use Milpa\Command\Effect\Externality;
use Milpa\Command\Effect\Mutation;
use Milpa\Command\Effect\Reversibility;
use Milpa\Live\ValueObjects\ActionContract;
use Milpa\Live\ValueObjects\ComponentContract;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** An action says what it is FOR, and a contradiction is refused where it is written. */
#[CoversClass(ActionContract::class)]
#[CoversClass(ComponentContract::class)]
final class ActionContractTest extends TestCase
{
    public function testAnUndeclaredActionSaysNobodySaidRatherThanSayingItIsSafe(): void
    {
        $action = new ActionContract();

        self::assertNull($action->effects);
        self::assertFalse($action->declaresEffects());
        self::assertFalse($action->mutating);
        self::assertSame('', $action->summary);
    }

    public function testABareArrayActionReadsAsADeclarationWithNothingDeclared(): void
    {
        $contract = new ComponentContract('x', '1', actions: [
            'fire' => ['payload' => ['event' => 'string'], 'scopeBy' => 'event'],
        ]);

        $action = $contract->action('fire');

        self::assertInstanceOf(ActionContract::class, $action);
        self::assertSame('event', $action->scopeBy);
        self::assertSame(['event' => 'string'], $action->payload);
        self::assertFalse($action->declaresEffects(), 'a bare array declared nothing about effects');
    }

    public function testARichActionKeepsWhatItDeclared(): void
    {
        $contract = new ComponentContract('x', '1', actions: [
            'save' => new ActionContract(
                summary: 'Persist the draft.',
                mutating: true,
                effects: self::mutatingProfile(),
                namedTarget: 'draftId',
            ),
        ]);

        $action = $contract->action('save');

        self::assertInstanceOf(ActionContract::class, $action);
        self::assertSame('Persist the draft.', $action->summary);
        self::assertTrue($action->mutating);
        self::assertTrue($action->declaresEffects());
        self::assertSame('draftId', $action->namedTarget);
    }

    public function testAnActionThatIsNotDeclaredAtAllIsNull(): void
    {
        self::assertNull((new ComponentContract('x', '1'))->action('nope'));
    }

    public function testNotMutatingBesideAProfileThatMutatesIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/declared not mutating/');

        new ActionContract(mutating: false, effects: self::mutatingProfile());
    }

    public function testMutatingBesideAProfileThatDoesNotMutateIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/declared mutating/');

        new ActionContract(mutating: true, effects: EffectProfile::readOnly());
    }

    public function testACoherentDeclarationIsAcceptedInBothDirections(): void
    {
        self::assertTrue((new ActionContract(mutating: true, effects: self::mutatingProfile()))->mutating);
        self::assertFalse((new ActionContract(mutating: false, effects: EffectProfile::readOnly()))->mutating);
    }

    private static function mutatingProfile(): EffectProfile
    {
        return new EffectProfile(
            mutation: Mutation::Persistent,
            externality: Externality::None,
            reversibility: Reversibility::Compensatable,
            authority: Authority::WriteAsUser,
        );
    }
}
