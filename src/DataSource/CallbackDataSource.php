<?php

/**
 * This file is part of Milpa Live — the render-target-agnostic live component core of the Milpa PHP framework.
 *
 * (c) TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

namespace Milpa\Live\DataSource;

use Milpa\Live\Contracts\Data\DataSourceInterface;
use Milpa\Live\ValueObjects\DataSourceRequest;
use Milpa\Live\ValueObjects\DataSourceResult;

/**
 * A {@see DataSourceInterface} backed by a caller-supplied `callable` —
 * the escape hatch for a data source that isn't a fixed array (a database
 * query, an HTTP call, …) without needing its own class per case.
 */
final readonly class CallbackDataSource implements DataSourceInterface
{
    /**
     * `$callback`'s return is intentionally typed `mixed`, not narrowed to
     * `DataSourceResult|array<int, array<string, mixed>>` — the callable
     * itself is caller-supplied and unenforced at the type level (the
     * property below is `mixed`), so {@see resolve()}'s runtime guard
     * below is a real, reachable defense against a misbehaving callback,
     * not dead code.
     *
     * @param callable(DataSourceRequest): mixed $callback
     */
    public function __construct(
        private string $name,
        private mixed $callback,
    ) {
    }

    /** True when `$source` matches this data source's registered name. */
    public function supports(string $source): bool
    {
        return $source === $this->name;
    }

    /**
     * Invokes the wrapped callback with `$request`; accepts either a
     * {@see DataSourceResult} back (returned as-is) or a plain item array
     * (wrapped in one).
     *
     * @throws \RuntimeException If the callback returns anything else.
     */
    public function resolve(DataSourceRequest $request): DataSourceResult
    {
        $result = ($this->callback)($request);

        if ($result instanceof DataSourceResult) {
            return $result;
        }

        if (is_array($result)) {
            return new DataSourceResult($result, ['source' => $this->name]);
        }

        throw new \RuntimeException("Callback data source {$this->name} returned an invalid result.");
    }
}
