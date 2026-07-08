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

namespace Milpa\Live\Tests\Support;

use Milpa\Live\Support\MilpaCore;
use PHPUnit\Framework\TestCase;

/**
 * Converted from `tests/smoke.php` lines ~1297-1324, trimmed per the
 * fase-B partition §5's "light unit test" guidance. The lab's version also
 * asserts against the LAB's own `composer.json` contents (e.g. the
 * `dev-main as 0.5.x-dev` path-repo alias) — those assertions are
 * environment-specific to the lab, not to this package's own
 * `composer.json` (`"milpa/core": "*"`), so they are intentionally not
 * ported. What's kept: {@see MilpaCore}'s own self-diagnostic contract —
 * the static metadata it reports, and that it correctly detects the
 * installed `milpa/core` dependency in *this* package's vendor tree.
 */
final class MilpaCoreTest extends TestCase
{
    public function testReportsItsStaticPackageMetadata(): void
    {
        self::assertSame('milpa/core', MilpaCore::package());
        self::assertSame('8.3.0', MilpaCore::minimumPhp());
        self::assertSame('Milpa\\', MilpaCore::namespacePrefix());
        self::assertSame('composer require milpa/core:' . MilpaCore::versionConstraint(), MilpaCore::installCommand());
    }

    public function testRuntimeCompatibilityHonorsTheMinimumPhpVersion(): void
    {
        self::assertTrue(MilpaCore::isRuntimeCompatible('8.3.0'));
        self::assertTrue(MilpaCore::isRuntimeCompatible('8.3.32'));
        self::assertFalse(MilpaCore::isRuntimeCompatible('8.2.32'));
    }

    public function testDetectsTheInstalledMilpaCoreDependency(): void
    {
        self::assertTrue(MilpaCore::isInstalled(), 'Expected milpa/core to be resolvable from this package\'s own vendor tree.');
        self::assertNotNull(MilpaCore::installedVersion());
    }

    public function testContractMapKeysAreAllReportedAvailable(): void
    {
        $contracts = MilpaCore::contractMap();
        self::assertArrayHasKey('pluginInterface', $contracts);
        self::assertSame('Milpa\\Interfaces\\Plugin\\PluginInterface', $contracts['pluginInterface']);

        foreach (MilpaCore::availableContracts() as $contract => $available) {
            self::assertTrue($available, "Expected mapped Milpa Core contract '{$contract}' to be autoloadable.");
        }
    }

    public function testStatusExposesTheFullDiagnosticShape(): void
    {
        $status = MilpaCore::status();

        self::assertSame('milpa/core', $status['package']);
        self::assertIsBool($status['runtimeCompatible']);
        self::assertIsBool($status['installed']);
        self::assertIsArray($status['contracts']);
        self::assertIsArray($status['availableContracts']);
    }
}
