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

namespace Ampache;

use ReflectionClass;
use ReflectionNamedType;

/**
 * Stubs the `make()` half of the container for tests that reach `Plugin::get_plugins()`
 *
 * A plugin is built with `make()` rather than `get()` because `load()` writes per-user state onto the
 * instance, so each caller needs its own. Tests therefore need a container mock that answers `make()`.
 */
trait PluginFactoryMockTrait
{
    /**
     * Builds a real instance of a class, handing every constructor dependency a mock
     *
     * @param class-string $className
     */
    protected function buildWithMockedDependencies(string $className): object
    {
        $reflection  = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        $arguments = [];
        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                $arguments[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;

                continue;
            }

            $arguments[] = $this->createMock($type->getName());
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
