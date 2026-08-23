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

namespace Ampache\Module\Art\Generated\Template;

/**
 * Finds the drawing templates by looking in this directory.
 *
 * Adding a design means dropping one class in beside the others: nothing else is edited, no service
 * definition to touch, no list to extend. Ampache already finds its themes the same way, by looking for
 * what is on disk rather than by keeping a register of it.
 */
final class TemplateRegistry implements TemplateRegistryInterface
{
    /** @var null|list<TemplateInterface> */
    private ?array $templates = null;

    public function all(): array
    {
        if ($this->templates !== null) {
            return $this->templates;
        }

        $found = [];
        foreach (glob(__DIR__ . '/*.php') ?: [] as $file) {
            $class = __NAMESPACE__ . '\\' . basename($file, '.php');
            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if (
                $reflection->isAbstract()
                || !$reflection->implementsInterface(TemplateInterface::class)
                || $reflection->getConstructor()?->getNumberOfRequiredParameters() > 0
            ) {
                continue;
            }

            /** @var TemplateInterface $template */
            $template                  = $reflection->newInstance();
            $found[$template->getId()] = $template;
        }

        // a stable order, so the preferences page does not reshuffle itself between page loads
        ksort($found);

        return $this->templates = array_values($found);
    }

    public function find(string $id): ?TemplateInterface
    {
        foreach ($this->all() as $template) {
            if ($template->getId() === $id) {
                return $template;
            }
        }

        return null;
    }
}
