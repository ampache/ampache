<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Config;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class DicBuilderTest extends TestCase
{
    /**
     * A domain whose service_definition.php is not listed here is simply absent from the container,
     * and every service it defines fails to resolve at runtime rather than at build time.
     */
    public function testEveryServiceDefinitionIsAddedToTheContainer(): void
    {
        $sourcePath = realpath(__DIR__ . '/../../src');

        self::assertIsString($sourcePath);

        $builder = (string) file_get_contents($sourcePath . '/Config/DicBuilder.php');

        $missing  = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getFilename() !== 'service_definition.php') {
                continue;
            }

            // DicBuilder lives in src/Config, so every path but its own is written relative to that
            // Paths are normalised to forward slashes so the literals match on Windows too
            $relative = str_replace(
                [$sourcePath, DIRECTORY_SEPARATOR],
                ['', '/'],
                (string) $file->getRealPath()
            );
            $literal  = ($relative === '/Config/service_definition.php')
                ? '/service_definition.php'
                : '/..' . $relative;

            if (!str_contains($builder, "__DIR__ . '" . $literal . "'")) {
                $missing[] = $relative;
            }
        }

        self::assertSame([], $missing, 'service_definition.php files missing from DicBuilder.php');
    }

    /**
     * Two services that constructor-inject each other resolve to a blank page rather than an error, because
     * `ApplicationRunner` swallows the exception -- so nothing else in the suite can see it.
     *
     * Entries that fail for other reasons are ignored: several need runtime config the suite has no database
     * for, and one registered key is an abstract class.
     */
    public function testNoRegisteredServiceHasACircularDependency(): void
    {
        $container = require __DIR__ . '/../../src/Config/DicBuilder.php';

        $circular = [];
        foreach ($this->getRegisteredServiceKeys() as $key) {
            try {
                $container->get($key);
            } catch (Throwable $error) {
                if (str_contains($error->getMessage(), 'Circular dependency detected')) {
                    $circular[$key] = $error->getMessage();
                }
            }
        }

        self::assertSame([], $circular);
    }

    /**
     * @return list<string>
     */
    private function getRegisteredServiceKeys(): array
    {
        $sourcePath = realpath(__DIR__ . '/../../src');

        self::assertIsString($sourcePath);

        $keys     = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getFilename() !== 'service_definition.php') {
                continue;
            }

            /** @var array<string, mixed> $definitions */
            $definitions = require $file->getRealPath();

            foreach (array_keys($definitions) as $key) {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }
}
