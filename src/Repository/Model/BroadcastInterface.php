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

interface BroadcastInterface extends
    library_item
{
    public function getId(): int;

    public function isNew(): bool;

    public function getName(): string;

    public function getUserId(): int;

    public function getIsPrivate(): int;

    public function getSongPosition(): int;

    public function setSongPosition(int $value): void;

    public function getSongId(): int;

    public function setSongId(int $value): void;

    public function getListeners(): int;

    public function setListeners(int $value): void;

    public function getStarted(): int;

    public function setStarted(int $value): void;

    public function getTags(): array;

    public function getTagsFormatted(): string;

    public function getLinkFormatted(): string;

    /**
     * Update broadcast state.
     * @param boolean $started
     * @param string $key
     */
    public function update_state(
        $started,
        $key = ''
    );

    /**
     * Update broadcast listeners.
     * @param integer $listeners
     */
    public function update_listeners($listeners);

    /**
     * Update broadcast current song.
     * @param integer $song_id
     */
    public function update_song($song_id);

    /**
     * Update a broadcast from data array.
     * @param array $data
     * @return integer
     */
    public function update(array $data);

    /**
     * @param boolean $details
     */
    public function format($details = true);

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords();

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname();

    /**
     * Get parent item description.
     * @return array|null
     */
    public function get_parent();

    /**
     * Get item childrens.
     * @return array
     */
    public function get_childrens();

    /**
     * Search for item childrens.
     * @param string $name
     * @return array
     */
    public function search_childrens($name);

    /**
     * Get all childrens and sub-childrens medias.
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null);

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs();

    /**
     * Get item's owner.
     * @return integer|null
     */
    public function get_user_owner();

    /**
     * Get default art kind for this item.
     * @return string
     */
    public function get_default_art_kind();

    /**
     * @return mixed|null
     */
    public function get_description();

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art(
        $thumb = 2,
        $force = false
    );

    /**
     * Get play url.
     *
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @return integer
     */
    public function play_url(
        $additional_params = '',
        $player = null,
        $local = false
    );

    public function isEnabled(): bool;
}
