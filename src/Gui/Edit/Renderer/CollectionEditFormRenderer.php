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

namespace Ampache\Gui\Edit\Renderer;

use Ampache\Gui\Edit\AbstractEditFormRenderer;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\User;
use Override;

/**
 * The collection edit dialog.
 */
final class CollectionEditFormRenderer extends AbstractEditFormRenderer
{
    /**
     * @return list<int>
     */
    public function getCollaborateIds(): array
    {
        $ids = [];
        foreach (explode(',', (string) $this->getItem()->collaborate) as $id) {
            if ($id !== '') {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * @return array<int, string>
     */
    public function getCollaborators(): array
    {
        return User::getValidArray();
    }

    public function getCollectionId(): int
    {
        return $this->getItem()->getId();
    }

    public function getName(): string
    {
        return (string) $this->getItem()->name;
    }

    public function getObjectType(): string
    {
        return $this->getItem()->object_type ?? '';
    }

    /**
     * Only types the current contents allow are offered, so a refused re-pin cannot be chosen in the first
     * place; `Collection::update()` still checks, because the API shares the same rule.
     *
     * @return list<string>
     */
    public function getSelectableTypes(): array
    {
        $current = $this->getObjectType();
        $types   = [];
        foreach (Collection::VALID_TYPES as $objectType) {
            if ($objectType !== $current && $this->getItem()->conflictingType($objectType) !== null) {
                continue;
            }

            $types[] = $objectType;
        }

        return $types;
    }

    public function getType(): string
    {
        return (string) $this->getItem()->type;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit/collection.phtml');
    }

    private function getItem(): Collection
    {
        /** @var Collection $item */
        $item = $this->getContext()->item;

        return $item;
    }
}
