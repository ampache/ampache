<?php

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

namespace Ampache\Module\Util;

/**
 * This class handles the retrieval of media tags
 */
interface VaInfoInterface
{
    /**
     * forceSize
     */
    public function forceSize(int $size): void;

    /**
     * get_info
     *
     * This function runs the various steps to gathering the metadata. Filling $this->tags
     */
    public function gather_tags(): void;

    /**
     * check_time
     * check a cached file is close to the expected time
     */
    public function check_time(int $time): bool;

    /**
     * write_id3
     * This function runs the various steps to gathering the metadata
     * @throws \Exception
     */
    public function write_id3(array $tagData): void;

    /**
     * prepare_metadata_for_writing
     * Prepares vorbiscomments/id3v2 metadata for writing tag to file
     * @param array $frames
     * @return array
     */
    public function prepare_metadata_for_writing(array $frames): array;

    /**
     * read_id3
     *
     * This function runs the various steps to gathering the metadata
     * @return array
     */
    public function read_id3(): array;

    /**
     * get_tag_type
     *
     * This takes the result set and the tag_order defined in your config
     * file and tries to figure out which tag type(s) it should use. If your
     * tag_order doesn't match anything then it throws up its hands and uses
     * everything in random order.
     * @param array $results
     * @param string $configKey
     * @return string[]
     */
    public static function get_tag_type(array $results, string $configKey = 'metadata_order'): array;

    /**
     * clean_tag_info
     *
     * This function takes the array from vainfo along with the
     * key we've decided on and the filename and returns it in a
     * sanitized format that Ampache can actually use
     * @param array $results
     * @param array $keys
     * @param string|null $filename
     * @return array<string, mixed>
     */
    public static function clean_tag_info(array $results, array $keys, ?string $filename = null): array;

    /**
     * parse_pattern
     * @param string $filepath
     * @param string $dirPattern
     * @param string $filePattern
     * @return array<string, mixed>
     */
    public static function parse_pattern(string $filepath, string $dirPattern, string $filePattern): array;

    /**
     * set_broken
     *
     * This fills all tag types with Unknown (Broken)
     *
     * @return array<string, array<string, string>> Return broken title, album, artist
     */
    public function set_broken(): array;
}
