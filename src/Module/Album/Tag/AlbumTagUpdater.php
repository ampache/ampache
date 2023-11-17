<?php

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
 */

declare(strict_types=1);

namespace Ampache\Module\Album\Tag;

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\SongRepositoryInterface;

final class AlbumTagUpdater implements AlbumTagUpdaterInterface
{
    private SongRepositoryInterface $songRepository;

    public function __construct(
        SongRepositoryInterface $songRepository
    ) {
        $this->songRepository = $songRepository;
    }

    /**
     * Update tags of albums and/or songs
     */
    public function updateTags(
        Album $album,
        string $tagsComma,
        bool $overrideChilds,
        bool $addToChilds,
        bool $forceUpdate = false
    ): void {
        // When current_id not empty we force to overwrite current object
        Tag::update_tag_list($tagsComma, 'album', $album->id, $forceUpdate ? true : $overrideChilds);

        if ($overrideChilds || $addToChilds) {
            $songs = $this->songRepository->getByAlbum($album->id);
            foreach ($songs as $song_id) {
                Tag::update_tag_list($tagsComma, 'song', $song_id, $overrideChilds);
            }
        }
    }
}
