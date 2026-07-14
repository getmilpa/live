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
 * A query against a named {@see \Milpa\Live\Contracts\Data\DataSourceInterface},
 * as issued by a component (e.g. an autocomplete's search-as-you-type).
 * `$limit` is a request, not a guarantee — a data source MAY return fewer
 * items and MUST report via {@see DataSourceResult::$meta} whether the
 * result was truncated.
 */
final readonly class DataSourceRequest
{
    /**
     * @param string               $source      The target data source's name, matched via {@see \Milpa\Live\Contracts\Data\DataSourceInterface::supports()}.
     * @param string               $componentId The requesting component instance's id.
     * @param string               $query       Free-text search term; an empty string means "no filtering by text".
     * @param int                  $limit       Maximum number of items requested.
     * @param array<string, mixed> $filters     Additional source-specific filter criteria beyond `$query`.
     * @param array<string, mixed> $context     Caller-defined extra context the data source may need to resolve the query.
     */
    public function __construct(
        public string $source,
        public string $componentId,
        public string $query = '',
        public int $limit = 20,
        public array $filters = [],
        public array $context = [],
    ) {
    }
}
