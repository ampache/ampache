<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Shout;

use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;

/**
 * Loads objects in context of the shout-box
 *
 * Shouts should only be linked to enabled library-items
 * (at least in some cases)
 */
final readonly class ShoutObjectLoader implements ShoutObjectLoaderInterface
{
    public function __construct(
        private LibraryItemLoaderInterface $libraryItemLoader
    ) {
    }

    /**
     * Loads a library item by its type and id and check if it may be used
     */
    public function loadByObjectType(LibraryItemEnum $type, int $object_id): ?library_item
    {
        $object = $this->libraryItemLoader->load(
            $type,
            $object_id
        );

        if ($object instanceof Song || $object instanceof Podcast_Episode || $object instanceof Video) {
            if (!$object->enabled) {
                $object = null;
            }
        }

        return $object;
    }

    /**
     * Loads the object the shout is linked to
     */
    public function loadByShout(Shoutbox $shout): ?library_item
    {
        return $this->loadByObjectType($shout->getObjectType(), $shout->getObjectId());
    }
}
