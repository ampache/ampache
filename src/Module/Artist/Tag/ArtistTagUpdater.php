<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Module\Artist\Tag;

use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Tag;
use Ampache\Module\Album\Tag\AlbumTagUpdaterInterface;
use Ampache\Repository\AlbumRepositoryInterface;

final class ArtistTagUpdater implements ArtistTagUpdaterInterface
{
    private AlbumRepositoryInterface $albumRepository;

    private AlbumTagUpdaterInterface $albumTagUpdater;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        AlbumTagUpdaterInterface $albumTagUpdater,
        ModelFactoryInterface $modelFactory
    ) {
        $this->albumRepository = $albumRepository;
        $this->albumTagUpdater = $albumTagUpdater;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * Update tags of artists and/or albums
     */
    public function updateTags(
        Artist $artist,
        string $tags_comma,
        bool $override_childs,
        ?int $add_to_childs,
        bool $force_update = false
    ): void {
        Tag::update_tag_list($tags_comma, 'artist', $artist->getId(), $force_update ? true : $override_childs);

        if ($override_childs || $add_to_childs) {
            $albums = $this->albumRepository->getByArtist($artist->id);

            foreach ($albums as $albumId) {
                $this->albumTagUpdater->updateTags(
                    $this->modelFactory->createAlbum($albumId),
                    $tags_comma,
                    $override_childs,
                    $add_to_childs
                );
            }
        }
    }
}
