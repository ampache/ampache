<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Generic loader for all kind of library-items
 *
 * Supports loading of all kind of library-items as defined within the LibraryItemEnum
 *
 * @see LibraryItemEnum
 */
final readonly class LibraryItemLoader implements LibraryItemLoaderInterface
{
    public function __construct(
        private ContainerInterface $dic
    ) {
    }

    /**
     * Loads a generic library-item
     *
     * Will try to load an item with the given object-type and -id.
     * Supports the specification of a list of allowed classes/interfaces to check against.
     *
     * @template TITemType of library_item
     *
     * @param list<class-string<TITemType>> $allowedItems List of all possible class-/interface-names
     *
     * @return null|TITemType
     */
    public function load(
        LibraryItemEnum $objectType,
        int $objectId,
        array $allowedItems = [library_item::class]
    ): ?library_item {
        $object = match ($objectType) {
            LibraryItemEnum::ALBUM => new Album($objectId),
            LibraryItemEnum::ALBUM_DISK => new AlbumDisk($objectId),
            LibraryItemEnum::ART => new Art($objectId),
            LibraryItemEnum::ARTIST => new Artist($objectId),
            LibraryItemEnum::BROADCAST => new Broadcast($objectId),
            LibraryItemEnum::CLIP => new Clip($objectId),
            LibraryItemEnum::LABEL => $this->dic->get(LabelRepositoryInterface::class)->findById($objectId),
            LibraryItemEnum::LIVE_STREAM => $this->dic->get(LiveStreamRepositoryInterface::class)->findById($objectId),
            LibraryItemEnum::MOVIE => new Movie($objectId),
            LibraryItemEnum::PERSONAL_VIDEO => new Personal_Video($objectId),
            LibraryItemEnum::PLAYLIST => new Playlist($objectId),
            LibraryItemEnum::PODCAST => $this->dic->get(PodcastRepositoryInterface::class)->findById($objectId),
            LibraryItemEnum::PODCAST_EPISODE => new Podcast_Episode($objectId),
            LibraryItemEnum::SEARCH => new Search($objectId),
            LibraryItemEnum::SONG => new Song($objectId),
            LibraryItemEnum::SONG_PREVIEW => new Song_Preview($objectId),
            LibraryItemEnum::TAG_HIDDEN, LibraryItemEnum::TAG => new Tag($objectId),
            LibraryItemEnum::TV_SHOW => new TvShow($objectId),
            LibraryItemEnum::TV_SHOW_EPISODE => new TVShow_Episode($objectId),
            LibraryItemEnum::TV_SHOW_SEASON => new TVShow_Season($objectId),
            LibraryItemEnum::VIDEO => new Video($objectId),
        };

        if (!($object?->isNew())) {
            foreach ($allowedItems as $className) {
                if ($object instanceof $className) {
                    return $object;
                }
            }
        }

        return null;
    }
}
