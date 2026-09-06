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

namespace Milpa\Live\Tests\Components;

use Milpa\Live\Components\Library;
use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The declaration is measured against the directory, not trusted.
 *
 * A hand-kept list of what a directory contains is a second source of truth, and this package
 * refuses those everywhere else. So the list is asserted to be exactly the concrete component
 * classes on disk: add one and forget to declare it, or declare one that was deleted, and this
 * reds — which is the only reason the catalogue built on top of it can be believed.
 */
#[CoversClass(Library::class)]
final class LibraryIsTheDirectoryTest extends TestCase
{
    public function testTheDeclarationIsExactlyTheConcreteComponentsOnDisk(): void
    {
        $onDisk = $this->concreteComponentClasses();
        $declared = (new Library())->declaredComponents();

        sort($onDisk);
        $sorted = $declared;
        sort($sorted);

        self::assertSame($onDisk, $sorted, 'src/Components and Library::declaredComponents() disagree');
    }

    public function testEveryDeclaredClassIsAComponentDefinition(): void
    {
        foreach ((new Library())->declaredComponents() as $class) {
            self::assertTrue(class_exists($class), $class . ' is declared but does not exist');
            self::assertTrue(
                is_subclass_of($class, ComponentDefinitionInterface::class),
                $class . ' is declared but is not a ' . ComponentDefinitionInterface::class,
            );
        }
    }

    public function testTheDeclarationHasNoDuplicates(): void
    {
        $declared = (new Library())->declaredComponents();

        self::assertSame(array_values(array_unique($declared)), $declared);
    }

    /**
     * Every concrete component class under src/Components, found by walking the directory —
     * abstract bases are skipped because a catalogue cannot mount one.
     *
     * @return list<class-string<ComponentDefinitionInterface>>
     */
    private function concreteComponentClasses(): array
    {
        $root = \dirname(__DIR__, 2) . '/src/Components';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        $found = [];
        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), \strlen($root) + 1, -4);
            $class = 'Milpa\\Live\\Components\\' . str_replace('/', '\\', $relative);

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || !$reflection->implementsInterface(ComponentDefinitionInterface::class)) {
                continue;
            }

            $found[] = $class;
        }

        return $found;
    }
}
