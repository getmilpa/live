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
 * The client-side files a rendered view needs the HOST page to load — script
 * and stylesheet URLs a plugin serves from its own routes (greenhouse
 * decisions/0211, "declared views").
 *
 * A renderer that needs them implements
 * {@see \Milpa\Live\Contracts\Rendering\DeclaresClientAssets}; a compiler
 * collects one of these per rendered node and {@see merge()}s them; the host
 * emits the result ONCE, in declared order. Merging deduplicates by URL and
 * keeps first-seen order, so two components that share a module never make
 * the page load it twice — the value is immutable, every operation returns a
 * new instance.
 */
final readonly class ClientAssets
{
    /** @var list<string> script URLs, in the order they should load, each once */
    public array $scripts;

    /** @var list<string> stylesheet URLs, in the order they should apply, each once */
    public array $styles;

    /**
     * Canonical from birth: each list is deduplicated by URL at construction, first-seen order kept.
     *
     * @param list<string> $scripts script URLs, in the order they should load
     * @param list<string> $styles  stylesheet URLs, in the order they should apply
     */
    public function __construct(array $scripts = [], array $styles = [])
    {
        $this->scripts = self::dedupe($scripts);
        $this->styles = self::dedupe($styles);
    }

    /** No assets at all — what a renderer with nothing to declare, or a compile with no declaring renderer, yields. */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * A copy with `$scripts` / `$styles` appended — each list deduplicated by
     * URL, first-seen order preserved.
     *
     * @param list<string> $scripts
     * @param list<string> $styles
     */
    public function with(array $scripts = [], array $styles = []): self
    {
        return new self(
            scripts: self::dedupe([...$this->scripts, ...$scripts]),
            styles: self::dedupe([...$this->styles, ...$styles]),
        );
    }

    /** A copy with every URL of `$other` appended, deduplicated by URL, this instance's order first. */
    public function merge(self $other): self
    {
        return $this->with($other->scripts, $other->styles);
    }

    /** True when there is nothing to emit. */
    public function isEmpty(): bool
    {
        return $this->scripts === [] && $this->styles === [];
    }

    /**
     * The assets as data — `{scripts: [...], styles: [...]}`.
     *
     * @return array{scripts: list<string>, styles: list<string>}
     */
    public function toArray(): array
    {
        return ['scripts' => $this->scripts, 'styles' => $this->styles];
    }

    /**
     * @param list<string> $urls
     *
     * @return list<string>
     */
    private static function dedupe(array $urls): array
    {
        $seen = [];
        $unique = [];
        foreach ($urls as $url) {
            if (isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $unique[] = $url;
        }

        return $unique;
    }
}
