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

interface ChannelInterface extends Media, library_item
{
    public function getId(): int;

    public function getFixedEndpoint(): int;

    public function getUrl(): string;

    public function getDescription(): string;

    public function getName(): string;

    public function getBitrate(): int;

    public function getLoop(): int;

    public function getRandom(): int;

    public function getStreamType(): string;

    public function getObjectId(): int;

    public function getObjectType(): string;

    public function getPeakListeners(): int;

    public function setPeakListeners(int $value): void;

    public function getMaxListeners(): int;

    public function getListeners(): int;

    public function setListeners(int $value): void;

    public function getPid(): int;

    public function setPid(int $value): void;

    public function getStartDate(): int;

    public function setStartDate(int $value): void;

    public function getPort(): int;

    public function setPort(int $value): void;

    public function getInterface(): string;

    public function setInterface(string $value): void;

    public function getIsPrivate(): int;

    public function isNew(): bool;

    /**
     * get_genre
     * @return string
     */
    public function get_genre();

    /**
     * delete
     */
    public function delete(): bool;

    /**
     * update
     * @param array $data
     * @return integer
     */
    public function update(array $data);

    /**
     * format
     * @param boolean $details
     */
    public function format($details = true);

    /**
     * @return array<int, array{
     *  user: int,
     *  id: int,
     *  name: string
     * }>
     */
    public function getTags(): array;

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords();

    /**
     * get_fullname
     * @return string
     */
    public function get_fullname();

    /**
     * @return array{object_type: string, object_id: int}|null
     */
    public function get_parent(): ?array;

    /**
     * get_childrens
     * @return array
     */
    public function get_childrens();

    /**
     * search_childrens
     * @param string $name
     * @return array
     */
    public function search_childrens($name);

    /**
     * get_medias
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null);

    /**
     * get_user_owner
     * @return boolean|null
     */
    public function get_user_owner();

    /**
     * get_default_art_kind
     * @return string
     */
    public function get_default_art_kind();

    /**
     * get_description
     * @return string
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
     * get_target_object
     * @return ?Playlist
     */
    public function get_target_object();

    /**
     * get_stream_url
     * show the internal interface used for the stream
     * e.g. http://0.0.0.0:8200/stream.mp3
     *
     * @return string
     */
    public function get_stream_url();

    /**
     * get_stream_proxy_url
     * show the external address used for the stream
     * e.g. https://music.com.au/channel/6/stream.mp3
     *
     * @return string
     */
    public function get_stream_proxy_url();

    /**
     * get_stream_proxy_url_status
     * show the external address used for the stream
     * e.g. https://music.com.au/channel/6/status.xsl
     *
     * @return string
     */
    public function get_stream_proxy_url_status();

    /**
     * get_channel_state
     * @return string
     */
    public function get_channel_state();

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs();

    /**
     * play_url
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @return string
     */
    public function play_url(
        $additional_params = '',
        $player = null,
        $local = false
    );

    /**
     * get_stream_types
     * @param string $player
     * @return string[]
     */
    public function get_stream_types($player = null);

    /**
     * get_stream_name
     * @return string
     */
    public function get_stream_name();

    /**
     * @param integer $user
     * @param string $agent
     * @param array $location
     * @param integer $date
     * @return boolean
     */
    public function set_played(
        $user,
        $agent,
        $location,
        $date = null
    );

    /**
     * @param integer $user
     * @param string $agent
     * @param integer $date
     * @return boolean
     */
    public function check_play_history(
        $user,
        $agent,
        $date
    );

    /**
     * @param $target
     * @param $player
     * @param array $options
     * @return boolean
     */
    public function get_transcode_settings(
        $target = null,
        $player = null,
        $options = array()
    );

    public function remove();

    public function isEnabled(): bool;
}
