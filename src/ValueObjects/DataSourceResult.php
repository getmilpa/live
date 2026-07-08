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

namespace Milpa\Live\ValueObjects;

/**
 * The result of resolving a {@see DataSourceRequest} against a
 * {@see \Milpa\Live\Contracts\Data\DataSourceInterface}.
 */
final readonly class DataSourceResult
{
    /**
     * @param array<int, array<string, mixed>> $items Matching items, already limited/filtered by the data source.
     * @param array<string, mixed>             $meta  Source-defined metadata about the result (e.g. `total`,
     *                                                `truncated`); shape is not standardized across data sources.
     */
    public function __construct(
        public array $items,
        public array $meta = [],
    ) {
    }
}
