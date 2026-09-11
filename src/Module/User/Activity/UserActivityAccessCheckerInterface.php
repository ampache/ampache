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

use Ampache\Repository\Model\User;

interface UserActivityAccessCheckerInterface
{
    /**
     * Whether the viewer's own catalogs cover the object an activity was recorded against.
     *
     * An activity whose object carries no catalog of its own (a `follow`'s target user, a playlist, a
     * type the loader doesn't recognise) is always visible; only a `CatalogItemInterface` object with a
     * real catalog id is checked against the viewer's catalog list.
     */
    public function isVisibleTo(Useractivity $useractivity, User $viewer): bool;
}
