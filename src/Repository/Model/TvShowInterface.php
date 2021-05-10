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

namespace Ampache\Repository\Model;

interface TvShowInterface extends library_item
{
    public function getYear(): int;

    public function getSummary(): string;

    public function getPrefix(): string;

    public function getName(): string;

    public function getLinkFormatted(): string;

    /**
     * gets the tv show seasons id list
     */
    public function get_seasons(): array;

    /**
     * get_episodes
     * gets all episodes for this tv show
     */
    public function get_episodes();

    public function getCatalogId(): int;

    public function getEpisodeCount(): int;

    public function getNameFormatted(): string;

    public function getLink(): string;

    public function getTags(): array;

    public function getTagsFormatted(): string;

    /**
     * update_tags
     *
     * Update tags of tv shows
     * @param string $tags_comma
     * @param boolean $override_childs
     * @param boolean $add_to_childs
     * @param boolean $force_update
     */
    public function update_tags(
        $tags_comma,
        $override_childs,
        $add_to_childs,
        $force_update = false
    );

    public function remove(): bool;
}
