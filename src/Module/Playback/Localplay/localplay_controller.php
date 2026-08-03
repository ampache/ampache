<?php

declare(strict_types=1);

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

namespace Ampache\Module\Playback\Localplay;

use Ampache\Module\Playback\Stream_Url;

/**
 * localplay_controller Class
 *
 * This is the abstract class for any Localplay controller
 *
 */
abstract class localplay_controller
{
    // For display we need the following 'instance' functions

    /**
     * @param array<string, string> $data
     */
    abstract public function add_instance(array $data): void;

    // Required Functions
    /**
     * add_url
     */
    abstract public function add_url(Stream_Url $url): bool; // Takes an array of song_ids

    abstract public function clear_playlist(): bool;

    abstract public function connect(): bool;

    abstract public function delete_instance(int $uid): void;

    /**
     * Takes a single object_id and removes it from the playlist
     */
    abstract public function delete_track(int $object_id): bool;

    abstract public function get(): array;

    abstract public function get_active_instance(): ?int;

    abstract public function get_description(): string; // Returns the description

    abstract public function get_instance(?string $instance = ''): array;

    /**
     * @return string[]
     */
    abstract public function get_instances(): array;

    abstract public function get_version(): string; // Returns the version of this plugin

    abstract public function install(): bool;

    /**
     * @return array<
     *     string,
     *     array{description: string, type: string}
     * >
     */
    abstract public function instance_fields(): array;

    abstract public function is_installed(): bool;

    abstract public function next(): bool;

    /**
     * parse_url
     * This takes an Ampache URL and then returns the 'primary' part of it
     * So that it's easier for Localplay modules to return valid song information
     */
    public function parse_url(string $url): array
    {
        // Define possible 'primary' keys
        $primary_array = ['oid', 'demo_id', 'random'];
        $data          = [];

        // Query-string urls (`...?oid=123`) parse cleanly, so try them first.
        $variables = parse_url($url, PHP_URL_QUERY);
        if ($variables) {
            parse_str($variables, $data);
            foreach ($primary_array as $pkey) {
                if (array_key_exists($pkey, $data)) {
                    $data['primary_key'] = $pkey;

                    return $data;
                }
            }
        }

        // Path-style urls (the default `/play/.../oid/123/...` as well as beautiful urls) carry no query
        // string, so pull the primary key out of the path. This must run regardless of `stream_beautiful_url`
        // because the API forces that setting off while still queueing path-style urls into the player.
        preg_match('/oid[\=|\/](.*?)[\&|\/]/', $url, $match);
        if (array_key_exists(1, $match) && $match[1]) {
            return [
                'primary_key' => 'oid',
                'oid' => $match[1]
            ];
        }

        // callers read `demo_id`, so return it under that key (not `oid`)
        preg_match('/demo_id[=\/]([0-9]+)/', $url, $match);
        if (array_key_exists(1, $match) && $match[1]) {
            return [
                'primary_key' => 'demo_id',
                'demo_id' => (int) $match[1]
            ];
        }

        // match path-style urls too and keep `random` as the primary key the callers switch on
        preg_match_all('#\b(random_id|random_type)[=/]([^&/]+)#', $url, $match);
        if ($match[1] && $match[2]) {
            $result = array_combine($match[1], $match[2]);
            if (isset($result['random_id'])) {
                return [
                    'primary_key' => 'random',
                    'random_id' => (int) $result['random_id'],
                    'random_type' => $result['random_type'] ?? 'song'
                ];
            }
        }

        return $data;
    }

    abstract public function pause(): bool;

    abstract public function play(): bool;

    abstract public function prev(): bool;

    abstract public function random(bool $state): bool;

    abstract public function repeat(bool $state): bool;

    abstract public function set_active_instance(int $uid): bool;

    abstract public function skip(int $track_id): bool;

    abstract public function status(): array;

    abstract public function stop(): bool;

    abstract public function uninstall(): bool;

    /**
     * @param array<string, string> $data
     */
    abstract public function update_instance(int $uid, array $data): void;

    abstract public function volume(int $volume): bool;

    abstract public function volume_down(): bool;

    abstract public function volume_up(): bool;
}
