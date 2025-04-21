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

namespace Ampache\Module\Util;

use Ampache\Config\AmpConfig;
use Ampache\Module\LastFm\Exception\LastFmQueryFailedException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\LastFm\LastFmQueryInterface;
use Ampache\Module\System\Dba;
use SimpleXMLElement;
use Ampache\Repository\Model\Song;

class Recommendation
{
    /**
     * get_lastfm_results
     * Runs a last.fm query and returns the parsed results
     * @param string $method
     * @param string $query
     * @return SimpleXMLElement
     * @throws LastFmQueryFailedException
     */
    public static function get_lastfm_results(string $method, string $query): SimpleXMLElement
    {
        global $dic;

        return $dic->get(LastFmQueryInterface::class)->getLastFmResults($method, $query);
    }

    /**
     * garbage_collection
     *
     * This cleans out old recommendations cache
     */
    public static function garbage_collection(): void
    {
        $sql = <<<SQL
            DELETE FROM `recommendation`
            WHERE `last_update` < ? OR
                (
                    (`object_type` = 'song' AND `object_id` NOT IN (SELECT `id` FROM `song`)) OR
                    (`object_type` = 'artist' AND `object_id` NOT IN (SELECT `id` FROM `artist`)) OR
                    (`object_type` = 'album' AND `object_id` NOT IN (SELECT `id` FROM `album`))
                );
            SQL;
        Dba::write($sql, [(time() - 31556952)]);
        Dba::write("UPDATE `recommendation_item` SET `mbid` = NULL WHERE `mbid` = '';");
    }

    /**
     * @param string $object_type
     * @param int $object_id
     */
    public static function has_recommendation_cache($object_type, $object_id): bool
    {
        $sql        = "SELECT `id` FROM `recommendation` WHERE `object_type` = ? AND `object_id` = ?";
        $db_results = Dba::read($sql, [$object_type, $object_id]);
        $row        = Dba::fetch_assoc($db_results);
        if (empty($row)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $type
     * @param int $object_id
     * @param bool $get_items
     * @return null|array{
     *     id: int,
     *     last_update: string,
     *     items?: list<array{id: ?int, name: string, rel: ?string, mbid: ?string}>
     * }
     */
    protected static function get_recommendation_cache(string $type, int $object_id, bool $get_items = false): ?array
    {
        if (!AmpConfig::get('cron_cache')) {
            self::garbage_collection();
        }

        $cache      = null;
        $sql        = "SELECT `id`, `last_update` FROM `recommendation` WHERE `object_type` = ? AND `object_id` = ?";
        $db_results = Dba::read($sql, [$type, $object_id]);
        if ($row = Dba::fetch_assoc($db_results)) {
            $cache = [
                'id' => (int)$row['id'],
                'last_update' => (string)$row['last_update'],
            ];
        }

        if (is_array($cache) && $get_items) {
            $cache['items'] = [];
            $sql            = "SELECT `recommendation_id` AS `id`, `name`, `rel`, `mbid` FROM `recommendation_item` WHERE `recommendation` = ?";
            $db_results     = Dba::read($sql, [$cache['id']]);
            while ($results = Dba::fetch_assoc($db_results)) {
                $cache['items'][] = [
                    'id' => ($results['id'] !== null) ? (int)$results['id'] : null,
                    'name' => (string)$results['name'],
                    'rel' => ($results['rel'] !== null) ? (string)$results['rel'] : null,
                    'mbid' => ($results['mbid'] !== null) ? (string)$results['mbid'] : null,
                ];
            }

            return $cache;
        }

        return null;
    }

    /**
     * delete_recommendation_cache
     */
    protected static function delete_recommendation_cache(string $type, int $object_id): void
    {
        $cache = self::get_recommendation_cache($type, $object_id);
        if ($cache !== null && array_key_exists('id', $cache)) {
            Dba::write('DELETE FROM `recommendation_item` WHERE `recommendation` = ?', [$cache['id']]);
            Dba::write('DELETE FROM `recommendation` WHERE `id` = ?', [$cache['id']]);
        }
    }

    /**
     * update_recommendation_cache
     * Updates the recommendation cache for a given object type and object ID with provided recommendations.
     *
     * @param string $type
     * @param int $object_id
     * @param array<array{
     *     id?: ?int,
     *     name?: ?string,
     *     rel?: ?string,
     *     mbid?: ?string
     * }> $recommendations
     * @return void
     */
    protected static function update_recommendation_cache(string $type, int $object_id, array $recommendations): void
    {
        if (count($recommendations) > 0) {
            self::delete_recommendation_cache($type, $object_id);
            $sql = "INSERT INTO `recommendation` (`object_type`, `object_id`, `last_update`) VALUES (?, ?, ?)";
            Dba::write($sql, [$type, $object_id, time()]);
            $insertid = Dba::insert_id();
            foreach ($recommendations as $recommendation) {
                $sql = "INSERT INTO `recommendation_item` (`recommendation`, `recommendation_id`, `name`, `rel`, `mbid`) VALUES (?, ?, ?, ?, ?)";
                Dba::write($sql, [
                    $insertid,
                    $recommendation['id'] ?? null,
                    $recommendation['name'] ?? null,
                    $recommendation['rel'] ?? null,
                    $recommendation['mbid'] ?? null
                ]);
            }
        }
    }

    /**
     * get_songs_like
     * Returns a list of similar songs
     * @param int $song_id
     * @param int $limit
     * @param bool $local_only
     * @return list<array{
     *     id: ?int,
     *     name: string,
     *     rel: ?string,
     *     mbid?: ?string
     * }>
     */
    public static function get_songs_like(int $song_id, int $limit = 5, bool $local_only = true): array
    {
        if (!AmpConfig::get('lastfm_api_key')) {
            return [];
        }

        $song   = new Song($song_id);
        $artist = new Artist($song->artist);

        if ($artist->isNew()) {
            debug_event(
                self::class,
                sprintf(
                    'Artist `%d` of Song `%d` is invalid, skip recommendation lookup',
                    $song->artist,
                    $song->id
                ),
                LegacyLogger::LOG_LEVEL_WARNING
            );

            return [];
        }

        $fullname = (string)$artist->get_fullname();
        $query    = ($artist->mbid) ? 'mbid=' . rawurlencode($artist->mbid) : 'artist=' . rawurlencode($fullname);

        if (!empty($song->mbid)) {
            $query = 'mbid=' . rawurlencode($song->mbid);
        }

        $cache = self::get_recommendation_cache('song', $song_id, true);
        if ($cache === null || !array_key_exists('id', $cache)) {
            $similars = [];
            try {
                $xml = self::get_lastfm_results('track.getsimilar', $query);
                if ($xml->similartracks) {
                    $catalog_disable = AmpConfig::get('catalog_disable');
                    foreach ($xml->similartracks->children() as $child) {
                        $song_name = $child->name;
                        $local_id  = null;

                        $artist_name = $child->artist->name;
                        $searchname  = Catalog::trim_prefix((string)$artist_name);
                        $s_name      = $searchname['string'];
                        $s_fullname  = trim(trim((string)$searchname['prefix']) . ' ' . trim((string)$searchname['string']));

                        if ($catalog_disable) {
                            $sql = <<<SQL
                                SELECT `song`.`id`
                                FROM `song`
                                    LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`
                                    LEFT JOIN `catalog` ON `song`.`catalog` = `catalog`.`id`
                                WHERE `song`.`title` = ? AND
                                      (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) AND
                                      `catalog`.`enabled` = '1'
                                SQL;
                        } else {
                            $sql = <<<SQL
                                SELECT `song`.`id`
                                FROM `song`
                                    LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`
                                WHERE `song`.`title` = ? AND
                                      (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?)
                                SQL;
                        }
                        $db_result = Dba::read($sql, [$song_name, $s_name, $s_fullname]);
                        if ($result = Dba::fetch_assoc($db_result)) {
                            $local_id = (int)$result['id'];
                            debug_event(self::class, "$song_name matched local song $local_id", 4);
                            $similars[] = [
                                'id' => $local_id,
                                'name' => $song_name,
                                'rel' => $artist_name
                            ];
                        } else {
                            //debug_event(self::class, "$name did not match any local song", 5);
                            $similars[] = [
                                'id' => null,
                                'name' => $song_name,
                                'rel' => $artist_name
                            ];
                        }
                    }
                    self::update_recommendation_cache('song', $song_id, $similars);
                }
            } catch (LastFmQueryFailedException) {
                // Ignore request errors here
            }
        }

        if (!isset($similars) || count($similars) == 0) {
            $similars = $cache['items'] ?? [];
        }
        if ($similars) {
            $results = [];
            foreach ($similars as $similar) {
                /** @var array{id: ?int, name: string, rel: ?string, mbid: ?string} $similar */
                if (!$local_only || $similar['id'] !== null) {
                    $results[] = $similar;
                }

                if ($limit && count($results) >= $limit) {
                    break;
                }
            }
        }

        if (isset($results)) {
            return $results;
        }

        return [];
    }

    /**
     * get_artists_like
     * Returns a list of similar artists
     * @param int $artist_id
     * @param int $limit
     * @param bool $local_only
     * @return list<array{
     *     id: ?int,
     *     name: string,
     *     rel?: ?string,
     *     mbid?: ?string
     * }>
     */
    public static function get_artists_like(int $artist_id, int $limit = 10, bool $local_only = true): array
    {
        if (!AmpConfig::get('lastfm_api_key')) {
            return [];
        }

        $cache = self::get_recommendation_cache('artist', $artist_id, true);
        if ($cache === null || !array_key_exists('id', $cache)) {
            $artist   = new Artist($artist_id);
            $similars = [];
            $fullname = (string)$artist->get_fullname();
            $query    = ($artist->mbid) ? 'mbid=' . rawurlencode($artist->mbid) : 'artist=' . rawurlencode($fullname);

            try {
                $xml = self::get_lastfm_results('artist.getsimilar', $query);
                if ($xml->similarartists) {
                    $catalog_disable = AmpConfig::get('catalog_disable');
                    $enable_filter   = Catalog::get_enable_filter('artist', '`artist`.`id`');
                    foreach ($xml->similarartists->children() as $child) {
                        $name     = (string) $child->name;
                        $mbid     = (string) $child->mbid;
                        $local_id = null;

                        // First we check by MBID
                        if ($mbid) {
                            $sql = ($catalog_disable)
                                ? "SELECT `artist`.`id` FROM `artist` WHERE `mbid` = ? AND " . $enable_filter
                                : "SELECT `artist`.`id` FROM `artist` WHERE `mbid` = ?";

                            $db_result = Dba::read($sql, [$mbid]);
                            if ($result = Dba::fetch_assoc($db_result)) {
                                $local_id = $result['id'];
                            }
                        }

                        // Then we fall back to the less likely to work exact name match
                        if ($local_id === null) {
                            $searchname = Catalog::trim_prefix($name);
                            $s_name     = $searchname['string'];
                            $s_fullname = trim(trim((string)$searchname['prefix']) . ' ' . trim((string)$searchname['string']));
                            $sql        = ($catalog_disable)
                                ? "SELECT `artist`.`id` FROM `artist` WHERE (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) AND " . $enable_filter
                                : "SELECT `artist`.`id` FROM `artist` WHERE (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?)";

                            $db_result = Dba::read($sql, [$s_name, $s_fullname]);
                            if ($result = Dba::fetch_assoc($db_result)) {
                                $local_id = $result['id'];
                            }
                        }

                        // Then we give up
                        if ($local_id === null) {
                            //debug_event(self::class, "$name did not match any local artist", 5);
                            $similars[] = [
                                'id' => null,
                                'name' => $name,
                                'mbid' => $mbid
                            ];
                        } else {
                            debug_event(self::class, "$name matched local artist " . $local_id, 5);
                            $similars[] = [
                                'id' => $local_id,
                                'name' => $name
                            ];
                        }
                    }
                    if (count($similars) > 0) {
                        self::update_recommendation_cache('artist', $artist_id, $similars);
                    }
                }
            } catch (LastFmQueryFailedException) {
                // Ignore request errors here
            }
        }

        if (!isset($similars) || count($similars) == 0) {
            $similars = $cache['items'] ?? [];
        }
        $results = [];
        if ($similars) {
            foreach ($similars as $similar) {
                if (!$local_only || $similar['id'] !== null) {
                    $results[] = $similar;
                }

                if ($limit && count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * get_artist_info_by_name
     * Returns artist information by full name.
     * @param string $fullname The full name of the artist.
     * @return array{
     *     summary: ?string,
     *     placeformed: ?string,
     *     yearformed: ?string
     * }
     */
    public static function get_artist_info_by_name(string $fullname): array
    {
        $query = 'artist=' . rawurlencode($fullname);

        $results = [
            'summary' => null,
            'placeformed' => null,
            'yearformed' => null,
        ];

        try {
            $xml = self::get_lastfm_results('artist.getinfo', $query);
        } catch (LastFmQueryFailedException) {
            return $results;
        }

        $results['summary'] = strip_tags(
            (string)preg_replace(
                "#<a href=([^<]*)Last\.fm</a>.#",
                "",
                (string)$xml->artist->bio->summary
            )
        );
        $results['summary']     = str_replace("Read more on Last.fm", "", $results['summary']);
        $results['placeformed'] = (string)$xml->artist->bio->placeformed;
        $results['yearformed']  = (string)$xml->artist->bio->yearformed;

        return $results;
    }

    /**
     * get_artist_info
     * Returns artist information
     * @param int $artist_id
     * @return array{
     *     id: ?int,
     *     summary: ?string,
     *     placeformed: ?string,
     *     yearformed: ?int,
     *     largephoto: ?string,
     *     smallphoto: ?string,
     *     mediumphoto: ?string,
     *     megaphoto: ?string
     * }
     */
    public static function get_artist_info(int $artist_id): array
    {
        $artist = new Artist($artist_id);
        $query  = ($artist->mbid)
            ? 'mbid=' . rawurlencode($artist->mbid)
            : 'artist=' . rawurlencode((string)$artist->get_fullname());

        // Data newer than 6 months, use it
        if (($artist->last_update + 15768000) > time() || $artist->manual_update) {
            $results                = [];
            $results['id']          = $artist_id;
            $results['summary']     = $artist->summary;
            $results['placeformed'] = $artist->placeformed;
            $results['yearformed']  = $artist->yearformed;
            $results['largephoto']  = Art::url($artist->id, 'artist', null, 174);
            $results['smallphoto']  = Art::url($artist->id, 'artist', null, 34);
            $results['mediumphoto'] = Art::url($artist->id, 'artist', null, 64);
            $results['megaphoto']   = Art::url($artist->id, 'artist', null, 300);

            return $results;
        }

        try {
            $xml = self::get_lastfm_results('artist.getinfo', $query);
        } catch (LastFmQueryFailedException) {
            return [
                'id' => $artist_id,
                'summary' => null,
                'placeformed' => null,
                'yearformed' => null,
                'largephoto' => null,
                'smallphoto' => null,
                'mediumphoto' => null,
                'megaphoto' => null,
            ];
        }

        $results = [
            'id' => null,
            'largephoto' => null,
            'smallphoto' => null,
            'mediumphoto' => null,
            'megaphoto' => null,
        ];
        $results['summary'] = strip_tags(
            (string)preg_replace(
                "#<a href=([^<]*)Last\.fm</a>.#",
                "",
                ($xml->artist->bio->summary ?? '')
            )
        );
        $results['summary']     = str_replace("Read more on Last.fm", "", $results['summary']);
        $results['placeformed'] = (isset($xml->artist->bio->yearformed))
            ? (string)$xml->artist->bio->placeformed
            : null;
        $results['yearformed'] = (isset($xml->artist->bio->yearformed))
            ? (int)$xml->artist->bio->yearformed
            : null;

        if ($artist->isNew() === false) {
            $results['id'] = $artist->id;
            if (!empty($results['summary'])) {
                $artist->update_artist_info($results['summary'], $results['placeformed'], $results['yearformed']);
            }
            $results['largephoto']  = Art::url($artist->id, 'artist', null, 174);
            $results['smallphoto']  = Art::url($artist->id, 'artist', null, 34);
            $results['mediumphoto'] = Art::url($artist->id, 'artist', null, 64);
            $results['megaphoto']   = Art::url($artist->id, 'artist', null, 300);
        }

        return $results;
    }

    /**
     * get_album_info
     * Returns album information
     * @param int $album_id
     * @return array{
     *     id: int,
     *     summary: ?string,
     *     largephoto: ?string,
     *     smallphoto: ?string,
     *     mediumphoto: ?string,
     *     megaphoto: ?string
     * }
     */
    public static function get_album_info(int $album_id): array
    {
        $album = new Album($album_id);
        $query = ($album->mbid)
            ? 'mbid=' . rawurlencode($album->mbid)
            : 'artist=' . rawurlencode((string)$album->get_artist_fullname()) . '&album=' . rawurlencode((string)$album->get_fullname());

        $results = [
            'id' => $album_id,
            'summary' => null,
            'largephoto' => null,
            'smallphoto' => null,
            'mediumphoto' => null,
            'megaphoto' => null,
        ];

        try {
            $xml = self::get_lastfm_results('album.getinfo', $query);
        } catch (LastFmQueryFailedException) {
            return $results;
        }

        $results['summary'] = strip_tags(
            (string)preg_replace(
                "#<a href=([^<]*)Last\.fm</a>.#",
                "",
                ($xml->album->wiki->summary ?? '')
            )
        );
        $results['summary'] = str_replace("Read more on Last.fm", "", $results['summary']);

        if ($album->isNew() === false) {
            $results['id']          = $album->id;
            $results['largephoto']  = Art::url($album->id, 'album', null, 174);
            $results['smallphoto']  = Art::url($album->id, 'album', null, 34);
            $results['mediumphoto'] = Art::url($album->id, 'album', null, 64);
            $results['megaphoto']   = Art::url($album->id, 'album', null, 300);
        }

        return $results;
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id): void
    {
        self::delete_recommendation_cache($object_type, $old_object_id);
    }
}
