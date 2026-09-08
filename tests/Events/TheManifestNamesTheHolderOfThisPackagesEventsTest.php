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

namespace Milpa\Live\Tests\Events;

use Milpa\Interfaces\Event\DeclaresEvents;
use Milpa\Interfaces\Event\EventDeclaration;
use Milpa\Live\Events\LiveEvents;
use PHPUnit\Framework\TestCase;

/**
 * The falsifier for the second slice of greenhouse decisions/0228: a host that
 * never constructs this package's emitter can still learn its events, because
 * the MANIFEST names the holder. Everything here is read from the package's own
 * `composer.json` on disk — never from the class list, never from prose — so a
 * renamed holder, a typo in the manifest string, or a holder that stops being a
 * {@see DeclaresEvents} goes red here instead of going silent in a host.
 */
final class TheManifestNamesTheHolderOfThisPackagesEventsTest extends TestCase
{
    /** @var list<class-string> */
    private const EXPECTED_HOLDERS = [LiveEvents::class];

    public function testTheManifestListsExactlyTheHoldersOfThisPackage(): void
    {
        self::assertSame(
            self::EXPECTED_HOLDERS,
            $this->holdersNamedInTheManifest(),
            'extra.milpa.events must name exactly this package\'s event holders, with escaped backslashes.',
        );
    }

    public function testEveryClassTheManifestNamesExistsAndIsAHolder(): void
    {
        foreach ($this->holdersNamedInTheManifest() as $named) {
            self::assertTrue(class_exists($named), "The manifest names {$named}, which does not exist.");
            self::assertTrue(
                is_a($named, DeclaresEvents::class, true),
                "The manifest names {$named}, which is not a " . DeclaresEvents::class . '.',
            );
        }
    }

    public function testTheClassNamedInTheManifestDeclaresTheSameEventsTheHolderDoes(): void
    {
        $fromTheManifest = [];
        foreach ($this->holdersNamedInTheManifest() as $named) {
            self::assertTrue(is_a($named, DeclaresEvents::class, true), "{$named} is not a holder.");
            /** @var list<EventDeclaration> $declarations */
            $declarations = $named::declarations();
            foreach ($declarations as $declaration) {
                $fromTheManifest[] = $declaration->name;
            }
        }

        $fromTheHolder = array_map(
            static fn (EventDeclaration $d): string => $d->name,
            LiveEvents::declarations(),
        );

        self::assertNotSame([], $fromTheManifest, 'The manifest route must reach real declarations, not an empty list.');
        self::assertSame($fromTheHolder, $fromTheManifest, 'Reading the manifest must yield the same event names as the holder itself.');
    }

    /**
     * The `extra.milpa.events` list, read from this package's composer.json on disk.
     *
     * @return list<string>
     */
    private function holdersNamedInTheManifest(): array
    {
        $path = dirname(__DIR__, 2) . '/composer.json';
        self::assertFileExists($path, 'The package manifest must be readable from the test suite.');

        $raw = file_get_contents($path);
        self::assertIsString($raw);

        $manifest = json_decode($raw, true);
        self::assertIsArray($manifest, 'composer.json must be valid JSON.');
        self::assertArrayHasKey('extra', $manifest, 'The manifest must carry an extra section.');
        self::assertIsArray($manifest['extra']);
        self::assertArrayHasKey('milpa', $manifest['extra'], 'The manifest must carry extra.milpa.');
        self::assertIsArray($manifest['extra']['milpa']);
        self::assertArrayHasKey('events', $manifest['extra']['milpa'], 'The manifest must name its event holders under extra.milpa.events.');

        $named = $manifest['extra']['milpa']['events'];
        self::assertIsArray($named, 'extra.milpa.events must be a list of fully-qualified class names.');

        $holders = [];
        foreach ($named as $entry) {
            self::assertIsString($entry, 'Every entry of extra.milpa.events must be a class name string.');
            $holders[] = $entry;
        }

        return $holders;
    }
}
