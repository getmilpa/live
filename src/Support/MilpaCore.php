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

namespace Milpa\Live\Support;

/**
 * Static facts and runtime checks about this package's one hard dependency,
 * `milpa/core` — the version constraint milpa/live was built against, which
 * `milpa/core` contracts it relies on, and whether the currently-installed
 * `milpa/core` actually satisfies them. Useful for a host application's own
 * diagnostics/health-check page, not consulted by milpa/live itself at
 * runtime.
 */
final class MilpaCore
{
    private const PACKAGE = 'milpa/core';
    private const VERSION_CONSTRAINT = '^0.5';
    private const RELEASE = 'v0.5.0';
    private const MINIMUM_PHP = '8.3.0';
    private const NAMESPACE_PREFIX = 'Milpa\\';

    /** The Composer package name milpa/live depends on: `"milpa/core"`. */
    public static function package(): string
    {
        return self::PACKAGE;
    }

    /** The Composer version constraint milpa/live requires `milpa/core` at. */
    public static function versionConstraint(): string
    {
        return self::VERSION_CONSTRAINT;
    }

    /** The `milpa/core` release tag milpa/live was built and verified against. */
    public static function release(): string
    {
        return self::RELEASE;
    }

    /** The minimum PHP version `milpa/core` (and therefore milpa/live) requires. */
    public static function minimumPhp(): string
    {
        return self::MINIMUM_PHP;
    }

    /** The namespace prefix `milpa/core`'s own symbols live under: `"Milpa\\"`. */
    public static function namespacePrefix(): string
    {
        return self::NAMESPACE_PREFIX;
    }

    /**
     * Maps a short, stable key (e.g. `'pluginInterface'`) to the fully
     * qualified `milpa/core` class/interface name it currently resolves to —
     * a single place to update if a `milpa/core` symbol is ever renamed.
     *
     * @return array<string, class-string>
     */
    public static function contractMap(): array
    {
        return [
            'pluginMetadata' => 'Milpa\\Attributes\\PluginMetadata',
            'pluginInterface' => 'Milpa\\Interfaces\\Plugin\\PluginInterface',
            'verifierInterface' => 'Milpa\\Interfaces\\Verification\\VerifierInterface',
            'verificationContext' => 'Milpa\\ValueObjects\\Verification\\VerificationContext',
            'verificationRequest' => 'Milpa\\ValueObjects\\Verification\\VerificationRequest',
            'verificationResult' => 'Milpa\\ValueObjects\\Verification\\VerificationResult',
            'semanticVersion' => 'Milpa\\ValueObjects\\SemanticVersion',
            'capabilityProvision' => 'Milpa\\ValueObjects\\Capability\\CapabilityProvision',
            'capabilityRequirement' => 'Milpa\\ValueObjects\\Capability\\CapabilityRequirement',
            'capabilitySuggestion' => 'Milpa\\ValueObjects\\Capability\\CapabilitySuggestion',
        ];
    }

    /**
     * Checks every {@see contractMap()} entry against the classes/interfaces
     * actually autoloadable right now, so a caller can tell "milpa/core is
     * installed" apart from "the specific symbols we depend on exist".
     *
     * @return array<string, bool>
     */
    public static function availableContracts(): array
    {
        $availability = [];
        foreach (self::contractMap() as $key => $class) {
            $availability[$key] = class_exists($class) || interface_exists($class);
        }

        return $availability;
    }

    /** True when `$phpVersion` (defaults to the running `PHP_VERSION`) meets {@see minimumPhp()}. */
    public static function isRuntimeCompatible(?string $phpVersion = null): bool
    {
        return version_compare($phpVersion ?? PHP_VERSION, self::MINIMUM_PHP, '>=');
    }

    /**
     * True when `milpa/core` is present — via `Composer\InstalledVersions`
     * when available, falling back to checking for its `composer.json` in
     * `vendor/` (e.g. under a `path` repository during local development).
     */
    public static function isInstalled(): bool
    {
        if (
            class_exists(\Composer\InstalledVersions::class)
            && \Composer\InstalledVersions::isInstalled(self::PACKAGE)
        ) {
            return true;
        }

        return is_file(self::installedComposerPath());
    }

    /**
     * The installed `milpa/core` version (via `Composer\InstalledVersions`,
     * falling back to its `composer.json`'s `version` field), or `null` when
     * neither source can resolve one.
     */
    public static function installedVersion(): ?string
    {
        if (
            class_exists(\Composer\InstalledVersions::class)
            && \Composer\InstalledVersions::isInstalled(self::PACKAGE)
        ) {
            $version = \Composer\InstalledVersions::getPrettyVersion(self::PACKAGE);

            return is_string($version) ? $version : null;
        }

        $path = self::installedComposerPath();
        if (!is_file($path)) {
            return null;
        }

        $metadata = json_decode((string) file_get_contents($path), true);
        if (!is_array($metadata)) {
            return null;
        }

        return isset($metadata['version']) && is_string($metadata['version'])
            ? $metadata['version']
            : null;
    }

    /**
     * A single snapshot combining every static fact and runtime check on
     * this class — the shape a diagnostics/health-check endpoint would
     * serialize directly.
     *
     * @return array<string, mixed>
     */
    public static function status(): array
    {
        return [
            'package' => self::PACKAGE,
            'versionConstraint' => self::VERSION_CONSTRAINT,
            'release' => self::RELEASE,
            'minimumPhp' => self::MINIMUM_PHP,
            'namespacePrefix' => self::NAMESPACE_PREFIX,
            'runtimeCompatible' => self::isRuntimeCompatible(),
            'installed' => self::isInstalled(),
            'installedVersion' => self::installedVersion(),
            'contracts' => self::contractMap(),
            'availableContracts' => self::availableContracts(),
            'installCommand' => self::installCommand(),
        ];
    }

    /** The `composer require` snippet that installs `milpa/core` at {@see versionConstraint()}. */
    public static function installCommand(): string
    {
        return 'composer require ' . self::PACKAGE . ':' . self::VERSION_CONSTRAINT;
    }

    private static function installedComposerPath(): string
    {
        return dirname(__DIR__, 2) . '/vendor/milpa/core/composer.json';
    }
}
