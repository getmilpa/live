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
 * A {@see DataSourceInterface} backed by a fixed, in-process PHP array —
 * the simplest data source, for static lookups, fixtures, and tests. Matches
 * against `label`/`value`/`search` (case-insensitive substring), truncated
 * to {@see DataSourceRequest::$limit}.
 */
final readonly class ArrayDataSource implements DataSourceInterface
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        private string $name,
        private array $items,
    ) {
    }

    /** True when `$source` matches this data source's registered name. */
    public function supports(string $source): bool
    {
        return $source === $this->name;
    }

    /**
     * Filters `$items` by {@see DataSourceRequest::$query} (case-insensitive
     * substring match against `label`/`value`/`search`), then truncates to
     * {@see DataSourceRequest::$limit}; `$result->meta['truncated']` reports
     * whether the match set was larger than the returned page.
     */
    public function resolve(DataSourceRequest $request): DataSourceResult
    {
        $query = mb_strtolower(trim($request->query));
        $items = $this->items;

        if ($query !== '') {
            $items = array_values(array_filter(
                $items,
                static function (array $item) use ($query): bool {
                    $haystack = implode(' ', [
                        (string) ($item['label'] ?? ''),
                        (string) ($item['value'] ?? ''),
                        (string) ($item['search'] ?? ''),
                    ]);

                    return str_contains(mb_strtolower($haystack), $query);
                }
            ));
        }

        $total = count($items);

        return new DataSourceResult(
            items: array_slice($items, 0, $request->limit),
            meta: [
                'source' => $this->name,
                'total' => $total,
                'truncated' => $total > $request->limit,
            ],
        );
    }
}
