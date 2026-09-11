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

namespace Ampache\Module\User\Activity;

use Ampache\Repository\Model\CatalogItemInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\User;

final readonly class UserActivityAccessChecker implements UserActivityAccessCheckerInterface
{
    public function __construct(
        private LibraryItemLoaderInterface $libraryItemLoader,
    ) {}

    public function isVisibleTo(Useractivity $useractivity, User $viewer): bool
    {
        $objectType = LibraryItemEnum::tryFrom($useractivity->object_type);
        if ($objectType === null) {
            return true;
        }

        $libitem = $this->libraryItemLoader->load($objectType, $useractivity->object_id);
        if (!$libitem instanceof CatalogItemInterface) {
            return true;
        }

        $catalogId = $libitem->getCatalogId();
        if ($catalogId <= 0) {
            return true;
        }

        return in_array($catalogId, User::get_user_catalogs($viewer->getId()), true);
    }
}
