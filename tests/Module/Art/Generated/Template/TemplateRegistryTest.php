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

use PHPUnit\Framework\TestCase;

/**
 * Designs are found by looking in the template directory rather than by keeping a list, so that adding
 * one means dropping a class in beside the others and nothing else. These guard that contract.
 */
class TemplateRegistryTest extends TestCase
{
    public function testFindReturnsNullForAnUnknownId(): void
    {
        $registry = new TemplateRegistry();

        $this->assertNull($registry->find('no-such-template'));
        $this->assertNotNull($registry->find('dark'));
    }

    public function testTheAbstractBaseIsNotOffered(): void
    {
        foreach ((new TemplateRegistry())->all() as $template) {
            $this->assertNotInstanceOf(\ReflectionClass::class, $template);
            $this->assertNotSame('', $template->getId());
            $this->assertNotSame('', $template->getLabel());
        }
    }

    public function testTheOrderIsStable(): void
    {
        $registry = new TemplateRegistry();
        $first    = array_map(static fn(TemplateInterface $t): string => $t->getId(), $registry->all());
        $second   = array_map(static fn(TemplateInterface $t): string => $t->getId(), (new TemplateRegistry())->all());

        $this->assertSame($first, $second, 'the preferences page must not reshuffle between page loads');
    }

    public function testTheShippedTemplatesAreFound(): void
    {
        $ids = array_map(
            static fn(TemplateInterface $template): string => $template->getId(),
            (new TemplateRegistry())->all()
        );

        $this->assertContains('dark', $ids);
        $this->assertContains('light', $ids);
    }
}
