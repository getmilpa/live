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

use Milpa\Live\ValueObjects\ComponentContract;
use Milpa\Live\ValueObjects\ComponentPresentation;
use PHPUnit\Framework\TestCase;

/**
 * A component declares what it needs on the page; every consumer asks one question and gets one answer.
 *
 * The normalisation is the whole point: if `presentation()` could return null, every render target
 * would branch on whether the component bothered to declare, and the branch nobody wrote is the one
 * that reintroduces "the component renders but its look lives in someone else's package".
 */
final class ComponentPresentationTest extends TestCase
{
    public function testAContractThatDeclaresNothingStillAnswers(): void
    {
        $contract = new ComponentContract(name: 'bare', contractVersion: '1');

        self::assertFalse($contract->presentation()->declaresAnything());
        self::assertNull($contract->presentation()->styles);
        self::assertNull($contract->presentation()->script);
    }

    public function testADeclaredPresentationSurvivesTheContract(): void
    {
        $contract = new ComponentContract(
            name: 'rating',
            contractVersion: '2',
            presentation: new ComponentPresentation(styles: '/pkg/rating.css', script: '/pkg/rating.js'),
        );

        self::assertTrue($contract->presentation()->declaresAnything());
        self::assertSame('/pkg/rating.css', $contract->presentation()->styles);
        self::assertSame('/pkg/rating.js', $contract->presentation()->script);
    }

    public function testDeclaringOnlyStylesIsEnoughToCount(): void
    {
        $presentation = new ComponentPresentation(styles: '/pkg/only.css');

        self::assertTrue($presentation->declaresAnything());
        self::assertNull($presentation->script);
    }
}
