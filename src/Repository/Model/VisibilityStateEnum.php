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

namespace Ampache\Repository\Model;

/**
 * What the edit form asks for when it sets the visibility of an album or an artist.
 *
 * Only the first two are states the row can be in: `hidden` is a single flag, so an item put away with
 * its children reads back as HIDDEN. HIDDEN_DISABLED is therefore an instruction, never a stored value,
 * and nothing reaches back the other way - returning to VISIBLE restores the listing and leaves
 * `song`.`enabled` alone, so a song disabled for its own reasons is never played again by accident.
 */
enum VisibilityStateEnum: string
{
    case HIDDEN          = 'hidden';
    case HIDDEN_DISABLED = 'hidden_disabled';
    case VISIBLE         = 'visible';

    /**
     * Whether the children are taken along: albums put away, songs made unplayable
     */
    public function cascades(): bool
    {
        return $this === self::HIDDEN_DISABLED;
    }

    /**
     * Whether the item itself leaves the listings
     */
    public function isHidden(): bool
    {
        return $this !== self::VISIBLE;
    }
}
