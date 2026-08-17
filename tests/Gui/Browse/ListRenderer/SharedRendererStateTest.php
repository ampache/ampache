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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\Gui\Edit\AbstractEditFormRenderer;
use Ampache\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

/**
 * The browse and edit renderers are shared services, so a value one render leaves on a property is read
 * by the next one: an album page's second disk section showed the first disk's songs that way.
 */
class SharedRendererStateTest extends MockeryTestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function rendererProvider(): array
    {
        $roots = [
            [AbstractBrowseListRenderer::class, __DIR__ . '/../../../../src/Gui/Browse/ListRenderer', 'Ampache\Gui\Browse\ListRenderer\\'],
            [AbstractEditFormRenderer::class, __DIR__ . '/../../../../src/Gui/Edit/Renderer', 'Ampache\Gui\Edit\Renderer\\'],
        ];

        $cases = [];
        foreach ($roots as [$base, $directory, $namespace]) {
            foreach ((array) glob($directory . '/*.php') as $file) {
                $className = $namespace . basename((string) $file, '.php');
                if (!class_exists($className) || !is_subclass_of($className, $base)) {
                    continue;
                }

                $cases[] = [$className, $base];
            }
        }

        return $cases;
    }

    /**
     * Anything a renderer builds for one render belongs in `cachePerRender()`, which is emptied per render.
     */
    #[DataProvider('rendererProvider')]
    public function testRendererKeepsNoStateOfItsOwn(string $className, string $base): void
    {
        $offenders = [];
        foreach (new ReflectionClass($className)->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() === $base || $property->isReadOnly() || $property->isStatic()) {
                continue;
            }

            $offenders[] = '$' . $property->getName();
        }

        $this->assertSame(
            [],
            $offenders,
            $className . ' is a shared service and must not hold a writable property; use cachePerRender()'
        );
    }
}
