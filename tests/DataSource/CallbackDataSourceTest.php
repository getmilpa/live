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

namespace Milpa\Live\Tests\DataSource;

use Milpa\Live\DataSource\CallbackDataSource;
use Milpa\Live\ValueObjects\DataSourceRequest;
use Milpa\Live\ValueObjects\DataSourceResult;
use PHPUnit\Framework\TestCase;

/**
 * Not part of the lab's `tests/smoke.php` (which never exercises
 * {@see CallbackDataSource} directly), but core-assigned per the fase-B
 * partition and worth covering directly since it is a public seam of the
 * package: a plain array return is wrapped into a {@see DataSourceResult},
 * an already-built result is passed through, and anything else is a
 * caller error.
 */
final class CallbackDataSourceTest extends TestCase
{
    public function testWrapsPlainArrayReturnIntoDataSourceResult(): void
    {
        $source = new CallbackDataSource('callback.source', function (DataSourceRequest $request): array {
            return [['value' => 'a', 'label' => 'A: ' . $request->query]];
        });

        $result = $source->resolve(new DataSourceRequest(source: 'callback.source', componentId: 'c1', query: 'x'));

        self::assertSame([['value' => 'a', 'label' => 'A: x']], $result->items);
        self::assertSame('callback.source', $result->meta['source']);
    }

    public function testPassesThroughAnAlreadyBuiltDataSourceResult(): void
    {
        $expected = new DataSourceResult(items: [['value' => 'b']], meta: ['total' => 1]);
        $source = new CallbackDataSource('callback.source', static fn (): DataSourceResult => $expected);

        $result = $source->resolve(new DataSourceRequest(source: 'callback.source', componentId: 'c1'));

        self::assertSame($expected, $result);
    }

    public function testInvalidCallbackReturnThrows(): void
    {
        $source = new CallbackDataSource('callback.source', static fn (): string => 'not-a-result');

        $this->expectException(\RuntimeException::class);
        $source->resolve(new DataSourceRequest(source: 'callback.source', componentId: 'c1'));
    }

    public function testSupportsMatchesOnlyItsOwnName(): void
    {
        $source = new CallbackDataSource('callback.source', static fn (): array => []);

        self::assertTrue($source->supports('callback.source'));
        self::assertFalse($source->supports('other.source'));
    }
}
