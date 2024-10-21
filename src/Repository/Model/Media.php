<?php

declare(strict_types=0);

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

namespace Ampache\Repository\Model;

/**
 * media Interface
 *
 * This defines how the media file classes should
 * work, this lists all required functions and the expected
 * input
 */
interface Media
{
    /**
     * get_stream_types
     *
     * Returns an array of strings
     * 'native' = can be streamed natively
     * 'transcode' = transcode required
     * @param string $player
     * @return array
     */
    public function get_stream_types($player = null): array;

    /**
     * play_url
     *
     * Returns the url to stream the specified object
     * @param string $additional_params
     * @param string $player
     * @param bool $local
     */
    public function play_url($additional_params = '', $player = '', $local = false): string;

    /**
     * get_stream_name
     * Get the complete name to display for the stream.
     */
    public function get_stream_name(): string;

    /**
     * get_transcode_settings
     *
     * Should only be called if 'transcode' was returned by get_stream_types
     * Returns a raw transcode command for this item; the optional target
     * parameter can be used to request a specific format instead of the
     * default from the configuration file.
     * @param string $target
     * @param string $player
     * @param array $options
     * @return array
     */
    public function get_transcode_settings($target = null, $player = null, $options = []): array;

    /**
     * getYear
     */
    public function getYear(): string;

    /**
     * @param int $user_id
     * @param string $agent
     * @param array $location
     * @param int $date
     */
    public function set_played($user_id, $agent, $location, $date): bool;

    /**
     * @param int $user
     * @param string $agent
     * @param int $date
     */
    public function check_play_history($user, $agent, $date): bool;

    /**
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool;

    /**
     * Returns the full/formatted name of the media items artist/author
     */
    public function get_artist_fullname(): string;

    /**
     * Returns the filename of the media-item
     */
    public function getFileName(): string;
}
