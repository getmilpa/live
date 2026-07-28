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

namespace Milpa\Live\Tests\Testing;

use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\Contracts\Rendering\ComponentRendererInterface;
use Milpa\Live\Testing\RendersTheSameComponent;
use Milpa\Live\ValueObjects\RenderRequest;
use Milpa\Live\ValueObjects\RenderResult;
use Milpa\Live\ValueObjects\RenderTarget;
use PHPUnit\Framework\TestCase;

/**
 * The conformance kit, run at home against the smallest renderer that can satisfy it.
 *
 * Two reasons this exists rather than trusting the surfaces to exercise it. A suite nobody runs in
 * the package that ships it is a suite whose own bugs are found by its consumers — and here that
 * means found by `milpa/live-web` and `milpa/live-tui`, which would each have to decide whether the
 * kit or their renderer was wrong. And the renderer below doubles as the reference for anyone
 * writing a new surface: this is the whole obligation, in forty lines, with nothing to imitate that
 * is not required.
 *
 * It lives in `tests/` on purpose. `milpa/live` renders nothing — that is the point of the package —
 * and shipping even a plain-text renderer would make the sentence false.
 */
final class ReferenceRendererConformanceTest extends TestCase
{
    use RendersTheSameComponent;

    protected function rendererUnderTest(): ComponentRendererInterface
    {
        return new class () implements ComponentRendererInterface {
            /** @var list<string> */
            private const SUPPORTED = ['metric-card'];

            public function supportsTarget(RenderTarget $target): bool
            {
                return $target === RenderTarget::ANSI;
            }

            public function render(ComponentDefinitionInterface $component, RenderRequest $request): RenderResult
            {
                $contract = $component::contract();
                if (!\in_array($contract->name, self::SUPPORTED, true)) {
                    throw new \InvalidArgumentException("unsupported component: {$contract->name}");
                }

                // Mount when the caller did not, and hand the snapshot back — the contract's rule,
                // and the one an implementer is most likely to skip because rendering works without
                // it right up until someone needs the state that was drawn.
                $state = $request->state ?? $component->mount($request->props, $request->context);

                $line = trim(implode(' ', array_map(
                    static fn (mixed $v): string => \is_scalar($v) ? (string) $v : '',
                    array_values($state->data + $state->meta),
                )));

                return new RenderResult(output: $line, state: $state, format: RenderTarget::ANSI);
            }
        };
    }

    protected function targetUnderTest(): RenderTarget
    {
        return RenderTarget::ANSI;
    }
}
