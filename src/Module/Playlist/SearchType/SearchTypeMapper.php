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
 *
 */

namespace Ampache\Module\Playlist\SearchType;

use Ampache\Repository\DatabaseTableNameEnum;
use Psr\Container\ContainerInterface;

/**
 * Maps a db object name to a search type
 */
final class SearchTypeMapper implements SearchTypeMapperInterface
{
    private ContainerInterface $dic;

    public function __construct(
        ContainerInterface $dic
    ) {
        $this->dic = $dic;
    }

    public function map(string $type): ?SearchTypeInterface
    {
        $map = [
            DatabaseTableNameEnum::ALBUM => function () {
                return $this->dic->get(AlbumSearchType::class);
            },
            DatabaseTableNameEnum::ARTIST => function () {
                return $this->dic->get(ArtistSearchType::class);
            },
            DatabaseTableNameEnum::LABEL => function () {
                return $this->dic->get(LabelSearchType::class);
            },
            DatabaseTableNameEnum::PLAYLIST => function () {
                return $this->dic->get(PlaylistSearchType::class);
            },
            DatabaseTableNameEnum::SONG => function () {
                return $this->dic->get(SongSearchType::class);
            },
            DatabaseTableNameEnum::TAG => function () {
                return $this->dic->get(TagSearchType::class);
            },
            DatabaseTableNameEnum::USER => function () {
                return $this->dic->get(UserSearchType::class);
            },
            DatabaseTableNameEnum::VIDEO => function () {
                return $this->dic->get(VideoSearchType::class);
            },
        ];

        $callable = $map[$type] ?? null;

        if ($callable === null) {
            return null;
        }

        return $callable();
    }
}
