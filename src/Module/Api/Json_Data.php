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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Metadata;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Useractivity;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

/**
 * Json_Data Class
 *
 * This class takes care of all of the JSON document stuff in Ampache these
 * are all static calls
 *
 */
class Json_Data
{
    // This is added so that we don't pop any webservers
    private static ?int $limit = 5000;

    private static int $offset = 0;

    private static ?int $count = null;

    /**
     * set_offset
     *
     * This takes an int and changes the offset
     *
     * @param int $offset Change the starting position of your results. (e.g 5001 when selecting in groups of 5000)
     */
    public static function set_offset(int $offset): void
    {
        self::$offset = (int)$offset;
    }

    /**
     * set_limit
     *
     * This sets the limit for any ampache transactions
     *
     * @param int|string $limit Set a limit on your results
     */
    public static function set_limit(int|string $limit): bool
    {
        if (!$limit) {
            return false;
        }

        self::$limit = (strtolower((string)$limit) == "none") ? null : (int)$limit;

        return true;
    }

    /**
     * set_count
     *
     * Set the total count of returned objects
     *
     * @param int $count
     */
    public static function set_count(int $count): void
    {
        self::$count = (int)$count;
    }

    /**
     * error
     *
     * This generates a JSON Error message
     * nothing fancy here...
     *
     * @param int|string $code Error code
     * @param string $string Error message
     * @param string $action Error method
     * @param string $type Error type
     */
    public static function error(int|string $code, string $string, string $action, string $type): string
    {
        $message = [
            "error" => [
                "errorCode" => (string)$code,
                "errorAction" => $action,
                "errorType" => $type,
                "errorMessage" => $string,
            ]
        ];

        return json_encode($message, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * success
     *
     * This generates a standard JSON Success message
     * nothing fancy here...
     *
     * @param string $string success message
     * @param array<string, string> $return_data
     * @return string
     */
    public static function success(string $string, array $return_data = []): string
    {
        $message = ["success" => $string];
        foreach ($return_data as $title => $data) {
            $message[$title] = $data;
        }

        return json_encode($message, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * empty
     *
     * This generates a JSON empty object
     * nothing fancy here...
     *
     * @param string|null $type object type
     */
    public static function empty(?string $type = null): string
    {
        if (empty($type)) {
            return json_encode([], JSON_PRETTY_PRINT) ?: '';
        }

        return json_encode(
            [
                "total_count" => 0,
                "md5" => md5(serialize([])),
                $type => []
            ],
            JSON_PRETTY_PRINT
        ) ?: '';
    }

    /**
     * genre_array
     *
     * This returns the formatted 'genre' array for a JSON document
     * @param list<array{id: int, name: string, is_hidden: int, count: int}> $tags
     */
    private static function genre_array(array $tags): array
    {
        $JSON = [];

        if (!empty($tags)) {
            $atags = [];
            foreach ($tags as $tag) {
                if (array_key_exists($tag['id'], $atags)) {
                    $atags[$tag['id']]['count']++;
                } else {
                    $atags[$tag['id']] = [
                        "name" => $tag['name'],
                        "count" => 1
                    ];
                }
            }

            foreach ($atags as $tag_id => $data) {
                $JSON[] = [
                    "id" => (string)$tag_id,
                    "name" => $data['name']
                ];
            }
        }

        return $JSON;
    }

    /**
     * index
     *
     * This takes an array of object_ids and return JSON based on the type of object
     *
     * @param list<int|string> $objects Array of object_ids (Mixed string|int)
     * @param string $type 'album_artist'|'album'|'artist'|'catalog'|'live_stream'|'playlist'|'podcast_episode'|'podcast'|'share'|'song_artist'|'song'|'video'
     * @param User $user
     * @param bool $include (add child id's of the object (in sub array by type))
     * @return string JSON Object "catalog"|"artist"|"album"|"song"|"playlist"|"share"|"podcast"|"podcast_episode"|"video"|"live_stream"
     */
    public static function index(array $objects, string $type, User $user, bool $include = false): string
    {
        $count  = self::$count ?? count($objects);
        $output = [];
        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }

        if ($include) {
            switch ($type) {
                case 'album_artist':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `album_map`.`album_id` FROM `album_map` WHERE `album_map`.`object_id` = ? AND `album_map`.`object_type` = 'album';";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => $row['album_id'],
                                "type" => 'album'
                            ];
                        }
                    }
                    break;
                case 'song_artist':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `album_map`.`album_id` FROM `album_map` WHERE `album_map`.`object_id` = ? AND `album_map`.`object_type` = 'song';";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => $row['album_id'],
                                "type" => 'album'
                            ];
                        }
                    }
                    break;
                case 'artist':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `album_map`.`album_id` FROM `album_map` WHERE `album_map`.`object_id` = ?;";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => $row['album_id'],
                                "type" => 'album'
                            ];
                        }
                    }
                    break;
                case 'album':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` = ?;";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => $row['id'],
                                "type" => 'song'
                            ];
                        }
                    }
                    break;
                case 'playlist':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        /**
                         * Strip smart_ from playlist id and compare to original
                         * smartlist = 'smart_1'
                         * playlist  = 1000000
                         */
                        if ((int)$object_id === 0) {
                            $playlist = new Search((int)str_replace('smart_', '', (string)$object_id), 'song', $user);
                            foreach ($playlist->get_items() as $song) {
                                $output[$object_id][] = [
                                    "id" => $song['object_id'],
                                    "type" => 'song'
                                ];
                            }
                        } else {
                            $sql        = "SELECT `playlist_data`.`id`, `playlist_data`.`object_id`, `playlist_data`.`object_type` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? ORDER BY `playlist_data`.`track`;";
                            $db_results = Dba::read($sql, [$object_id]);
                            while ($row = Dba::fetch_assoc($db_results)) {
                                $output[$object_id][] = [
                                    "id" => $row['object_id'],
                                    "type" => $row['object_type']
                                ];
                            }
                        }
                    }
                    break;
                case 'podcast':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `podcast_episode`.`id` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ?;";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => $row['id'],
                                "type" => 'podcast_episode'
                            ];
                        }
                    }
                    break;
                case 'catalog':
                case 'live_stream':
                case 'podcast_episode':
                case 'share':
                case 'song':
                case 'video':
                    // These objects don't have children
                    $output = $objects;
                    break;
            }
        } else {
            $output = $objects;
        }
        $output = json_encode([$type => $output], JSON_PRETTY_PRINT);
        if ($output !== false) {
            return $output;
        }

        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        return self::error('4710', sprintf(T_('Bad Request: %s'), $type), 'indexes', 'type');
    }

    /**
     * indexes
     *
     * This takes an array of object_ids and return JSON based on the type of object
     *
     * @param list<int|string> $objects Array of object_ids (Mixed string|int)
     * @param string $type 'album_artist'|'album'|'artist'|'catalog'|'live_stream'|'playlist'|'podcast_episode'|'podcast'|'share'|'song_artist'|'song'|'video'
     * @param User $user
     * @param bool $include (add the extra songs details if a playlist or podcast_episodes if a podcast)
     * @return string JSON Object "artist"|"album"|"song"|"playlist"|"share"|"podcast"|"podcast_episode"|"video"|"live_stream"
     */
    public static function indexes(array $objects, string $type, User $user, bool $include = false): string
    {
        // here is where we call the object type
        switch ($type) {
            case 'catalog':
                /** @var string $results */
                $results = self::catalogs($objects);
                break;
            case 'song':
                /** @var string $results */
                $results = self::songs($objects, $user);
                break;
            case 'album':
                $include_array = ($include) ? ['songs'] : [];
                /** @var string $results */
                $results = self::albums($objects, $include_array, $user);
                break;
            case 'album_artist':
            case 'artist':
            case 'song_artist':
                $include_array = ($include) ? ['songs', 'albums'] : [];
                /** @var string $results */
                $results = self::artists($objects, $include_array, $user);
                break;
            case 'playlist':
                $results = self::playlists($objects, $user, $include);
                break;
            case 'share':
                $results = self::shares($objects);
                break;
            case 'podcast':
                $results = self::podcasts($objects, $user, $include);
                break;
            case 'podcast_episode':
                /** @var string $results */
                $results = self::podcast_episodes($objects, $user);
                break;
            case 'video':
                /** @var string $results */
                $results = self::videos($objects, $user);
                break;
            case 'live_stream':
                $results = self::live_streams($objects);
                break;
            default:
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                $results = self::error('4710', sprintf(T_('Bad Request: %s'), $type), 'indexes', 'type');
        }

        return $results;
    }

    /**
     * lists
     *
     * This takes a name array of objects and return the data in JSON list object
     *
     * @param array{id: int|string, name: string}[] $objects Array of object_ids ["id" => 1, "name" => 'Artist Name']
     */
    public static function lists(array $objects): string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }

        $JSON    = [];
        $pattern = '/^(' . implode('\\s|', explode('|', AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La'))) . '\\s)(.*)/i';
        foreach ($objects as $object) {
            $trimmed  = Catalog::trim_prefix(trim((string)$object['name']), $pattern);
            $prefix   = $trimmed['prefix'] ?? null;
            $basename = $trimmed['string'];
            $JSON[]   = [
                "id" => (string)$object['id'],
                "name" => $object['name'],
                "prefix" => $prefix,
                "basename" => $basename,
            ];
        } // end foreach
        $output["list"] = $JSON;

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * browses
     *
     * This takes a name array of objects and return the data in JSON browse object
     *
     * @param array{id: int|string, name: string}[] $objects Array of object_ids ["id" => 1, "name" => 'Artist Name']
     */
    public static function browses(array $objects, ?int $parent_id, string $parent_type, string $child_type, ?int $catalog_id): string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
            "catalog_id" => (string)$catalog_id,
            "parent_id" => (string)$parent_id,
            "parent_type" => $parent_type,
            "child_type" => $child_type,
        ];
        $pattern = '/^(' . implode('\\s|', explode('|', AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La'))) . '\\s)(.*)/i';

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $JSON = [];
        foreach ($objects as $object) {
            $trimmed  = Catalog::trim_prefix(trim((string)$object['name']), $pattern);
            $prefix   = $trimmed['prefix'];
            $basename = $trimmed['string'];
            $JSON[]   = [
                "id" => (string)$object['id'],
                "name" => $object['name'],
                "prefix" => $prefix,
                "basename" => $basename
            ];
        } // end foreach
        $output["browse"] = $JSON;

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * live_streams
     *
     * This returns live_streams to the user, in a pretty JSON document with the information
     *
     * @param list<int|string> $objects
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function live_streams(array $objects, bool $object = true): string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $JSON = [];
        foreach ($objects as $live_stream_id) {
            $live_stream = new Live_Stream((int)$live_stream_id);
            if ($live_stream->isNew()) {
                continue;
            }

            $JSON[] = [
                "id" => (string)$live_stream_id,
                "name" => $live_stream->get_fullname(),
                "url" => $live_stream->url,
                "codec" => $live_stream->codec,
                "catalog" => (string)$live_stream->catalog,
                "site_url" => $live_stream->site_url
            ];
        } // end foreach
        if ($object) {
            $output["live_stream"] = $JSON;
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * licenses
     *
     * This returns licenses to the user, in a pretty JSON document with the information
     *
     * @param list<int|string> $objects Licence id's assigned to songs and artists
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function licenses(array $objects, bool $object = true): string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }

        $licenseRepository = self::getLicenseRepository();

        $JSON = [];
        foreach ($objects as $license_id) {
            $license = $licenseRepository->findById((int)$license_id);

            if ($license !== null) {
                $JSON[]  = [
                    "id" => (string)$license_id,
                    "name" => $license->getName(),
                    "description" => $license->getDescription(),
                    "external_link" => $license->getExternalLink()
                ];
            }
        } // end foreach
        if ($object) {
            $output["license"] = $JSON;
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * labels
     *
     * This returns labels to the user, in a pretty JSON document with the information
     *
     * @param list<int|string> $objects
     * @param bool $encode return the array and don't json_encode the data
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "label"
     */
    public static function labels(array $objects, bool $encode = true, bool $object = true): array|string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $JSON = [];

        $labelRepository = self::getLabelRepository();

        foreach ($objects as $label_id) {
            $label = $labelRepository->findById((int)$label_id);
            if ($label === null) {
                continue;
            }

            $JSON[] = [
                "id" => (string)$label_id,
                "name" => $label->get_fullname(),
                "artists" => $label->get_artist_count(),
                "summary" => $label->summary,
                "external_link" => $label->get_link(),
                "address" => $label->address,
                "category" => $label->category,
                "email" => $label->email,
                "website" => $label->website,
                "user" => (string)$label->user,
            ];
        } // end foreach

        if ($encode) {
            if ($object) {
                $output["label"] = $JSON;
            } else {
                $output = $JSON[0] ?? [];
            }

            return json_encode($output, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * genres
     *
     * This returns genres to the user, in a pretty JSON document with the information
     *
     * @param list<int|string> $objects Genre id's to include
     * @param bool $encode return the array and don't json_encode the data
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "label"
     */
    public static function genres(array $objects, bool $encode = true, bool $object = true): array|string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $JSON = [];
        foreach ($objects as $tag_id) {
            $tag    = new Tag((int)$tag_id);
            $merged = $tag->get_merged_tags();
            $merge  = [];
            foreach ($merged as $mergedTag) {
                $merge[] = [
                    "id" => $mergedTag['id'],
                    "name" => $mergedTag['name'],
                ];
            }
            $JSON[] = [
                "id" => (string)$tag_id,
                "name" => $tag->name,
                "albums" => $tag->album,
                "artists" => $tag->artist,
                "songs" => $tag->song,
                "videos" => $tag->video,
                "playlists" => 0,
                "live_streams" => 0,
                "is_hidden" => (bool)$tag->is_hidden,
                "merge" => $merge,
            ];
        } // end foreach

        if ($encode) {
            if ($object) {
                $output["genre"] = $JSON;
            } else {
                $output = $JSON[0] ?? [];
            }

            return json_encode($output, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty JSON document with the information
     * we want
     *
     * @param list<int|string> $objects Artist id's to include
     * @param string[] $include
     * @param User $user
     * @param bool $encode
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "artist"
     */
    public static function artists(array $objects, array $include, User $user, bool $encode = true, bool $object = true): array|string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && (self::$limit && $encode)) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        Rating::build_cache('artist', $objects);
        $JSON = [];
        foreach ($objects as $artist_id) {
            $artist = new Artist((int)$artist_id);
            if ($artist->isNew()) {
                continue;
            }

            $rating      = new Rating($artist->id, 'artist');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($artist->id, 'artist');

            // Build the Art URL, include session
            $art_url = AmpConfig::get_web_path() . '/image.php?object_id=' . $artist_id . '&object_type=artist';

            // Handle includes
            $albums = (in_array("albums", $include))
                ? self::albums(self::getAlbumRepository()->getAlbumByArtist($artist->id), [], $user, false)
                : [];
            $songs = (in_array("songs", $include))
                ? self::songs(self::getSongRepository()->getByArtist($artist->id), $user, false)
                : [];

            $JSON[] = [
                "id" => (string)$artist->id,
                "name" => $artist->get_fullname(),
                "prefix" => $artist->prefix,
                "basename" => $artist->name,
                "albums" => $albums,
                "albumcount" => $artist->album_count,
                "songs" => $songs,
                "songcount" => $artist->song_count,
                "genre" => self::genre_array($artist->get_tags()),
                "art" => $art_url,
                "has_art" => $artist->has_art(),
                "flag" => (bool)$flag->get_flag($user->getId()),
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "mbid" => $artist->mbid,
                "summary" => $artist->summary,
                "time" => (int)$artist->time,
                "yearformed" => (int)$artist->yearformed,
                "placeformed" => $artist->placeformed
            ];
        } // end foreach artists

        if ($encode) {
            if ($object) {
                $output["artist"] = $JSON;
            } else {
                $output = $JSON[0] ?? [];
            }

            return json_encode($output, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * albums
     *
     * This echos out a standard albums JSON document, it pays attention to the limit
     *
     * @param list<int|string> $objects Album id's to include
     * @param string[] $include
     * @param User $user
     * @param bool $encode
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "album"
     */
    public static function albums(array $objects, array $include, User $user, bool $encode = true, bool $object = true): array|string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');

        if (($count > self::$limit || self::$offset > 0) && (self::$limit && $encode)) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        Rating::build_cache('album', $objects);
        $JSON = [];
        foreach ($objects as $album_id) {
            $album = new Album((int)$album_id);
            if ($album->isNew()) {
                continue;
            }

            $rating      = new Rating($album->id, 'album');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($album->id, 'album');
            $year        = ($original_year && $album->original_year)
                ? $album->original_year
                : $album->year;

            // Build the Art URL, include session
            $art_url = AmpConfig::get_web_path() . '/image.php?object_id=' . $album->id . '&object_type=album';

            $objArray = [];

            $objArray['id']       = (string)$album->id;
            $objArray['name']     = $album->get_fullname();
            $objArray['prefix']   = $album->prefix;
            $objArray['basename'] = $album->name;
            if ($album->get_artist_fullname() != "") {
                $objArray['artist'] = [
                    "id" => (string)$album->findAlbumArtist(),
                    "name" => $album->get_artist_fullname(),
                    "prefix" => $album->artist_prefix,
                    "basename" => $album->artist_name
                ];
                $album_artists = [];
                foreach ($album->get_artists() as $artist_id) {
                    $album_artists[] = Artist::get_name_array_by_id($artist_id);
                }
                $objArray['artists'] = $album_artists;
                $song_artists        = [];
                foreach ($album->get_song_artists() as $artist_id) {
                    $song_artists[] = Artist::get_name_array_by_id($artist_id);
                }
                $objArray['songartists'] = $song_artists;
            }

            // Handle includes
            $songs = (in_array("songs", $include))
                ? self::songs(self::getSongRepository()->getByAlbum($album->id), $user, false)
                : [];

            $objArray['time']          = (int)$album->time;
            $objArray['year']          = (int)$year;
            $objArray['tracks']        = $songs;
            $objArray['songcount']     = (int)$album->song_count;
            $objArray['diskcount']     = (int)$album->disk_count;
            $objArray['type']          = $album->release_type;
            $objArray['genre']         = self::genre_array($album->get_tags());
            $objArray['art']           = $art_url;
            $objArray['has_art']       = $album->has_art();
            $objArray['flag']          = (bool)$flag->get_flag($user->getId());
            $objArray['rating']        = $user_rating;
            $objArray['averagerating'] = $rating->get_average_rating();
            $objArray['mbid']          = $album->mbid;

            $JSON[] = $objArray;
        } // end foreach

        if ($encode) {
            if ($object) {
                $output["album"] = $JSON;
            } else {
                $output = $JSON[0] ?? [];
            }

            return json_encode($output, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty JSON document
     *
     * @param list<int|string> $objects Playlist id's to include
     * @param User $user
     * @param bool $songs
     * @param bool $encode return the array and don't json_encode the data
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "playlist"
     */
    public static function playlists(array $objects, User $user, bool $songs = false, bool $encode = true, bool $object = true): array|string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_slice($objects, self::$offset, self::$limit);
        }

        $JSON = [];
        foreach ($objects as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist  = 1000000
             */
            if ((int)$playlist_id === 0) {
                $playlist = new Search((int) str_replace('smart_', '', (string)$playlist_id), 'song', $user);
                if ($playlist->isNew()) {
                    continue;
                }
                $object_type    = 'search';
                $playitem_total = $playlist->last_count;
            } else {
                $playlist = new Playlist((int)$playlist_id);
                if ($playlist->isNew()) {
                    continue;
                }
                $object_type    = 'playlist';
                $playitem_total = $playlist->get_media_count('song');
            }
            $art_url           = Art::url($playlist->id, $object_type, Core::get_request('auth'));
            $playlist_name     = $playlist->get_fullname();
            $playlist_user     = $playlist->user;
            $playlist_username = $playlist->username;
            $playlist_type     = $playlist->type;
            $last_update       = $playlist->last_update;

            if ($songs) {
                $items          = [];
                $playlisttracks = $playlist->get_items();
                foreach ($playlisttracks as $objects) {
                    $items[] = [
                        "id" => (string)$objects['object_id'],
                        "playlisttrack" => $objects['track'],
                    ];
                }

                // hash the results
                $md5 = md5(serialize($playlisttracks));
            } else {
                $items = (int)($playitem_total ?? 0);
                $md5   = null;
            }

            $rating          = new Rating($playlist->id, $object_type);
            $user_rating     = $rating->get_user_rating($user->getId());
            $flag            = new Userflag($playlist->id, $object_type);
            $has_access      = $playlist->has_access($user);
            $has_collaborate = $has_access ?: $playlist->has_collaborate($user);

            // Build this element
            $JSON[] = [
                "id" => (string)$playlist_id,
                "name" => $playlist_name,
                "owner" => $playlist_username,
                "user" => [
                    "id" => (string)$playlist_user,
                    "username" => $playlist_username
                ],
                "items" => $items,
                "type" => $playlist_type,
                "art" => $art_url,
                "has_access" => $has_access,
                "has_collaborate" => $has_collaborate,
                "has_art" => $playlist->has_art(),
                "flag" => (bool)$flag->get_flag($user->getId()),
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "md5" => $md5,
                "last_update" => $last_update,
            ];
        } // end foreach

        if ($encode) {
            if ($object) {
                $output["playlist"] = $JSON;
            } else {
                $output = $JSON[0] ?? [];
            }

            return json_encode($output, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * shares
     *
     * This returns shares to the user, in a pretty json document with the information
     *
     * @param list<int|string> $objects Share id's to include
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function shares(array $objects, bool $object = true): string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $JSON = [];
        foreach ($objects as $share_id) {
            $share                = new Share((int)$share_id);
            $share_name           = $share->getObjectName();
            $share_user           = $share->getUserName();
            $share_allow_stream   = (bool)$share->allow_stream;
            $share_allow_download = (bool)$share->allow_download;
            $share_creation_date  = $share->creation_date;
            $share_lastvisit_date = $share->lastvisit_date;
            $share_object_type    = $share->object_type;
            $share_object_id      = (string)$share->object_id;
            $share_expire_days    = (int)$share->expire_days;
            $share_max_counter    = (int)$share->max_counter;
            $share_counter        = (int)$share->counter;
            $share_secret         = $share->secret;
            $share_public_url     = $share->public_url;
            $share_description    = $share->description;
            // Build this element
            $JSON[] = [
                "id" => (string)$share_id,
                "name" => $share_name,
                "owner" => $share_user,
                "allow_stream" => $share_allow_stream,
                "allow_download" => $share_allow_download,
                "creation_date" => $share_creation_date,
                "lastvisit_date" => $share_lastvisit_date,
                "object_type" => $share_object_type,
                "object_id" => $share_object_id,
                "expire_days" => $share_expire_days,
                "max_counter" => $share_max_counter,
                "counter" => $share_counter,
                "secret" => $share_secret,
                "public_url" => $share_public_url,
                "description" => $share_description
            ];
        } // end foreach
        if ($object) {
            $output["share"] = $JSON;
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * bookmarks
     *
     * This returns bookmarks to the user, in a pretty json document with the information
     *
     * @param int[] $objects Bookmark id's to include
     * @param bool $include if true include the object in the bookmark
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function bookmarks(array $objects, bool $include = false, bool $object = true): string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }

        $bookmarkRepository = self::getBookmarkRepository();

        $count = 0;
        $JSON  = [];
        foreach ($objects as $bookmark_id) {
            $bookmark = $bookmarkRepository->findById($bookmark_id);
            if ($bookmark === null) {
                continue;
            }

            $bookmark_username      = $bookmark->getUserName();
            $bookmark_object_type   = $bookmark->object_type;
            $bookmark_object_id     = (string)$bookmark->object_id;
            $bookmark_position      = $bookmark->position;
            $bookmark_comment       = $bookmark->comment;
            $bookmark_creation_date = $bookmark->creation_date;
            $bookmark_update_date   = $bookmark->update_date;
            // Build this element
            $JSON[] = [
                "id" => (string)$bookmark_id,
                "owner" => $bookmark_username,
                "object_type" => $bookmark_object_type,
                "object_id" => $bookmark_object_id,
                "position" => $bookmark_position,
                "client" => $bookmark_comment,
                "creation_date" => $bookmark_creation_date,
                "update_date" => $bookmark_update_date
            ];

            $user = User::get_from_username($bookmark_username);
            if (
                $include &&
                $user !== null
            ) {
                switch ($bookmark_object_type) {
                    case 'song':
                        $JSON[$count]['song'] = self::songs([(int)$bookmark_object_id], $user, false, false);
                        break;
                    case 'podcast_episode':
                        $JSON[$count]['podcast_episode'] = self::podcast_episodes([(int)$bookmark_object_id], $user, false, false);
                        break;
                    case 'video':
                        $JSON[$count]['video'] = self::videos([(int)$bookmark_object_id], $user, false, false);
                        break;
                }
            }
            $count++;
        } // end foreach
        if ($object) {
            $output["bookmark"] = $JSON;
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * catalogs
     *
     * This returns catalogs to the user, in a pretty json document with the information
     *
     * @param list<int|string> $objects group of catalog id's
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function catalogs(array $objects, bool $object = true): string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $JSON = [];
        foreach ($objects as $catalog_id) {
            $catalog = Catalog::create_from_id((int)$catalog_id);
            if ($catalog === null) {
                break;
            }
            $catalog_name           = $catalog->name;
            $catalog_type           = $catalog->catalog_type;
            $catalog_gather_types   = $catalog->gather_types;
            $catalog_enabled        = (bool)$catalog->enabled;
            $catalog_last_add       = $catalog->last_add;
            $catalog_last_clean     = $catalog->last_clean;
            $catalog_last_update    = $catalog->last_update;
            $catalog_path           = $catalog->get_f_info();
            $catalog_rename_pattern = $catalog->rename_pattern;
            $catalog_sort_pattern   = $catalog->sort_pattern;
            // Build this element
            $JSON[] = [
                "id" => (string)$catalog_id,
                "name" => $catalog_name,
                "type" => $catalog_type,
                "gather_types" => $catalog_gather_types,
                "enabled" => $catalog_enabled,
                "last_add" => $catalog_last_add,
                "last_clean" => $catalog_last_clean,
                "last_update" => $catalog_last_update,
                "path" => $catalog_path,
                "rename_pattern" => $catalog_rename_pattern,
                "sort_pattern" => $catalog_sort_pattern
            ];
        } // end foreach
        if ($object) {
            $output["catalog"] = $JSON;
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * podcasts
     *
     * This returns podcasts to the user, in a pretty json document with the information
     *
     * @param list<int|string> $objects Podcast id's to include
     * @param User $user
     * @param bool $episodes include the episodes of the podcast
     * @param bool $encode return the array and don't json_encode the data
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "podcast"
     */
    public static function podcasts(array $objects, User $user, bool $episodes = false, bool $encode = true, bool $object = true): array|string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }

        $podcastRepository = self::getPodcastRepository();

        $JSON = [];
        foreach ($objects as $podcast_id) {
            $podcast = $podcastRepository->findById((int)$podcast_id);
            if ($podcast === null) {
                continue;
            }

            $rating              = new Rating((int)$podcast_id, 'podcast');
            $user_rating         = $rating->get_user_rating($user->getId());
            $flag                = new Userflag((int)$podcast_id, 'podcast');
            $art_url             = Art::url((int)$podcast_id, 'podcast', Core::get_request('auth'));
            $podcast_name        = $podcast->get_fullname();
            $podcast_description = $podcast->get_description();
            $podcast_language    = scrub_out($podcast->getLanguage());
            $podcast_copyright   = scrub_out($podcast->getCopyright());
            $podcast_feed_url    = $podcast->getFeedUrl();
            $podcast_generator   = scrub_out($podcast->getGenerator());
            $podcast_website     = scrub_out($podcast->getWebsite());
            $podcast_build_date  = $podcast->getLastBuildDate()->format(DATE_ATOM);
            $podcast_sync_date   = $podcast->getLastSyncDate()->format(DATE_ATOM);
            $podcast_public_url  = $podcast->get_link();
            $podcast_episodes    = [];
            if ($episodes) {
                $results          = $podcast->getEpisodeIds();
                $podcast_episodes = self::podcast_episodes($results, $user, false);
            }

            // Build this element
            $JSON[] = [
                "id" => (string)$podcast_id,
                "name" => $podcast_name,
                "description" => $podcast_description,
                "language" => $podcast_language,
                "copyright" => $podcast_copyright,
                "feed_url" => $podcast_feed_url,
                "generator" => $podcast_generator,
                "website" => $podcast_website,
                "build_date" => $podcast_build_date,
                "sync_date" => $podcast_sync_date,
                "public_url" => $podcast_public_url,
                "art" => $art_url,
                "has_art" => $podcast->has_art(),
                "flag" => (bool)$flag->get_flag($user->getId()),
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "podcast_episode" => $podcast_episodes
            ];
        } // end foreach

        if ($encode) {
            if ($object) {
                $output["podcast"] = $JSON;
            } else {
                $output = $JSON[0] ?? [];
            }

            return json_encode($output, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * podcast_episodes
     *
     * This returns podcasts to the user, in a pretty json document with the information
     *
     * @param list<int|string> $objects Podcast_Episode id's to include
     * @param User $user
     * @param bool $encode
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "podcast_episode"
     */
    public static function podcast_episodes(array $objects, User $user, bool $encode = true, bool $object = true): array|string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && (self::$limit && $encode)) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $JSON = [];
        foreach ($objects as $episode_id) {
            $episode = new Podcast_Episode((int)$episode_id);
            if ($episode->isNew()) {
                continue;
            }

            $rating      = new Rating($episode->id, 'podcast_episode');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($episode->id, 'podcast_episode');
            $art_url     = Art::url($episode->podcast, 'podcast', Core::get_request('auth'));
            $JSON[]      = [
                "id" => (string)$episode_id,
                "title" => $episode->get_fullname(),
                "name" => $episode->get_fullname(),
                "podcast" => [
                    "id" => $episode->podcast,
                    "name" => $episode->getPodcastName()
                ],
                "description" => $episode->get_description(),
                "category" => $episode->getCategory(),
                "author" => $episode->getAuthor(),
                "author_full" => $episode->getAuthor(),
                "website" => $episode->getWebsite(),
                "pubdate" => $episode->getPubDate()->format(DATE_ATOM),
                "state" => $episode->getState()->toDescription(),
                "filelength" => $episode->get_f_time(true),
                "filesize" => $episode->getSizeFormatted(),
                "filename" => $episode->getFileName(),
                "mime" => $episode->mime,
                "time" => (int)$episode->time,
                "size" => (int)$episode->size,
                "bitrate" => $episode->bitrate,
                "stream_bitrate" => $episode->bitrate,
                "rate" => (int)$episode->rate,
                "mode" => $episode->mode,
                "channels" => $episode->channels,
                "public_url" => $episode->get_link(),
                "url" => $episode->play_url('', 'api', false, $user->getId(), $user->streamtoken),
                "catalog" => (string)$episode->catalog,
                "art" => $art_url,
                "has_art" => $episode->has_art(),
                "flag" => (bool)$flag->get_flag($user->getId()),
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "playcount" => (int)$episode->total_count,
                "played" => (string)$episode->played
            ];
        }
        if (!$encode) {
            return $JSON;
        }
        if ($object) {
            $output["podcast_episode"] = $JSON;
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * songs
     *
     * This returns an array of songs populated from an array of song ids.
     * (Spiffy isn't it!)
     * @param list<int|string> $objects
     * @param User $user
     * @param bool $encode
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "song"
     */
    public static function songs(array $objects, User $user, bool $encode = true, bool $object = true): array|string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        Stream::set_session($_REQUEST['auth'] ?? '');
        $playlist_track = 0;

        if (($count > self::$limit || self::$offset > 0) && (self::$limit && $encode)) {
            $objects = array_slice($objects, self::$offset, self::$limit);
        }
        Song::build_cache($objects);
        $JSON = [];
        foreach ($objects as $song_id) {
            $song = new Song((int)$song_id);
            // If the song id is invalid/null
            if ($song->isNew()) {
                continue;
            }
            $song->fill_ext_info();
            $rating       = new Rating((int)$song_id, 'song');
            $user_rating  = $rating->get_user_rating($user->getId());
            $flag         = new Userflag((int)$song_id, 'song');
            $art_url      = Art::url($song->album, 'album', $_REQUEST['auth'] ?? '');
            $songType     = $song->type;
            $songMime     = $song->mime;
            $songBitrate  = $song->bitrate;
            $play_url     = $song->play_url('', 'api', false, $user->id, $user->streamtoken);
            $song_album   = self::getAlbumRepository()->getNames($song->album);
            $song_artist  = Artist::get_name_array_by_id($song->artist);
            $song_artists = [];
            foreach ($song->get_artists() as $artist_id) {
                $song_artists[] = Artist::get_name_array_by_id($artist_id);
            }
            $license     = $song->getLicense();
            $licenseLink = $license?->getExternalLink() ?: null;

            $playlist_track++;

            $objArray = [
                "id" => (string)$song->id,
                "title" => $song->get_fullname(),
                "name" => $song->get_fullname(),
                "artist" => [
                    "id" => (string)$song->artist,
                    "name" => $song_artist['name'],
                    "prefix" => $song_artist['prefix'],
                    "basename" => $song_artist['basename']
                ],
                "artists" => $song_artists,
                "album" => [
                    "id" => (string)$song->album,
                    "name" => $song_album['name'],
                    "prefix" => $song_album['prefix'],
                    "basename" => $song_album['basename']
                ]
            ];
            if ($song->get_album_artist_fullname() != "") {
                $album_artist = ($song->artist !== $song->albumartist)
                    ? Artist::get_name_array_by_id($song->albumartist)
                    : $song_artist;
                $objArray['albumartist'] = [
                    "id" => (string)$song->albumartist,
                    "name" => $album_artist['name'],
                    "prefix" => $album_artist['prefix'],
                    "basename" => $album_artist['basename']
                ];
            }

            $objArray['disk']                  = (int)$song->disk;
            $objArray['disksubtitle']          = $song->disksubtitle;
            $objArray['track']                 = (int)$song->track;
            $objArray['filename']              = $song->file;
            $objArray['genre']                 = self::genre_array($song->get_tags());
            $objArray['playlisttrack']         = $playlist_track;
            $objArray['time']                  = (int)$song->time;
            $objArray['year']                  = (int)$song->year;
            $objArray['format']                = $songType;
            $objArray['stream_format']         = $song->type;
            $objArray['bitrate']               = $songBitrate;
            $objArray['stream_bitrate']        = $song->bitrate;
            $objArray['rate']                  = (int)$song->rate;
            $objArray['mode']                  = $song->mode;
            $objArray['mime']                  = $songMime;
            $objArray['stream_mime']           = $song->mime;
            $objArray['url']                   = $play_url;
            $objArray['size']                  = (int)$song->size;
            $objArray['mbid']                  = $song->mbid;
            $objArray['art']                   = $art_url;
            $objArray['has_art']               = $song->has_art();
            $objArray['flag']                  = (bool)$flag->get_flag($user->getId());
            $objArray['rating']                = $user_rating;
            $objArray['averagerating']         = $rating->get_average_rating();
            $objArray['playcount']             = (int)$song->total_count;
            $objArray['catalog']               = $song->getCatalogId();
            $objArray['composer']              = $song->composer;
            $objArray['channels']              = $song->channels;
            $objArray['comment']               = $song->comment;
            $objArray['license']               = $licenseLink;
            $objArray['publisher']             = $song->label;
            $objArray['language']              = $song->language;
            $objArray['lyrics']                = $song->lyrics;
            $objArray['replaygain_album_gain'] = $song->replaygain_album_gain;
            $objArray['replaygain_album_peak'] = $song->replaygain_album_peak;
            $objArray['replaygain_track_gain'] = $song->replaygain_track_gain;
            $objArray['replaygain_track_peak'] = $song->replaygain_track_peak;
            $objArray['r128_album_gain']       = $song->r128_album_gain;
            $objArray['r128_track_gain']       = $song->r128_track_gain;

            /** @var Metadata $metadata */
            foreach ($song->getMetadata() as $metadata) {
                $field = $metadata->getField();

                if ($field !== null) {
                    $meta_name = str_replace(
                        [' ', '(', ')', '/', '\\', '#'],
                        '_',
                        $field->getName()
                    );
                    $objArray[$meta_name] = $metadata->getData();
                }
            }
            $JSON[] = $objArray;
        } // end foreach

        if ($encode) {
            if ($object) {
                $output["song"] = $JSON;
            } else {
                $output = $JSON[0] ?? [];
            }

            return json_encode($output, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * song_tags
     *
     * This returns an array of song tags populated from an array of song ids.
     *
     * @param list<int|string> $objects
     * @param User $user
     * @param bool $encode
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "song"
     */
    public static function song_tags(array $objects, User $user, bool $encode = true, bool $object = true): string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        Stream::set_session($_REQUEST['auth'] ?? '');
        $playlist_track = 0;

        if (($count > self::$limit || self::$offset > 0) && (self::$limit && $encode)) {
            $objects = array_slice($objects, self::$offset, self::$limit);
        }

        $JSON = [];
        foreach ($objects as $song_id) {
            $song = new Song((int)$song_id);
            // If the song id is invalid/null
            if ($song->isNew()) {
                continue;
            }
            $catalog = Catalog::create_from_id($song->catalog);
            if (!$catalog) {
                continue;
            }
            $results  = $catalog->get_media_tags($song, ['music'], '', '');
            $objArray = [
                'albumartist' => $results['albumartist'] ?? null,
                'album' => $results['album'] ?? null,
                'artist' => $results['artist'] ?? null,
                'artists' => $results['artists'] ?? null,
                'art' => $results['art'] ?? null,
                'audio_codec' => $results['audio_codec'] ?? null,
                'barcode' => $results['barcode'] ?? null,
                'bitrate' => $results['bitrate'] ?? null,
                'catalog' => $results['catalog'] ?? null,
                'catalog_number' => $results['catalog_number'] ?? null,
                'channels' => $results['channels'] ?? null,
                'comment' => $results['comment'] ?? null,
                'composer' => $results['composer'] ?? null,
                'description' => $results['description'] ?? null,
                'disk' => $results['disk'] ?? null,
                'disksubtitle' => $results['disksubtitle'] ?? null,
                'display_x' => $results['display_x'] ?? null,
                'display_y' => $results['display_y'] ?? null,
                'encoding' => $results['encoding'] ?? null,
                'file' => $results['file'] ?? null,
                'frame_rate' => $results['frame_rate'] ?? null,
                'genre' => $results['genre'] ?? null,
                'isrc' => $results['isrc'] ?? null,
                'language' => $results['language'] ?? null,
                'lyrics' => $results['lyrics'] ?? null,
                'mb_albumartistid' => $results['mb_albumartistid'] ?? null,
                'mb_albumartistid_array' => $results['mb_albumartistid_array'] ?? null,
                'mb_albumid_group' => $results['mb_albumid_group'] ?? null,
                'mb_albumid' => $results['mb_albumid'] ?? null,
                'mb_artistid' => $results['mb_artistid'] ?? null,
                'mb_artistid_array' => $results['mb_artistid_array'] ?? null,
                'mb_trackid' => $results['mb_trackid'] ?? null,
                'mime' => $results['mime'] ?? null,
                'mode' => $results['mode'] ?? null,
                'original_name' => $results['original_name'] ?? null,
                'original_year' => $results['original_year'] ?? null,
                'publisher' => $results['publisher'] ?? null,
                'r128_album_gain' => $results['r128_album_gain'] ?? null,
                'r128_track_gain' => $results['r128_track_gain'] ?? null,
                'rate' => $results['rate'] ?? null,
                'rating' => $results['rating'] ?? null,
                'release_date' => $results['release_date'] ?? null,
                'release_status' => $results['release_status'] ?? null,
                'release_type' => $results['release_type'] ?? null,
                'replaygain_album_gain' => $results['replaygain_album_gain'] ?? null,
                'replaygain_album_peak' => $results['replaygain_album_peak'] ?? null,
                'replaygain_track_gain' => $results['replaygain_track_gain'] ?? null,
                'replaygain_track_peak' => $results['replaygain_track_peak'] ?? null,
                'size' => $results['size'] ?? null,
                'version' => $results['version'] ?? null,
                'summary' => $results['summary'] ?? null,
                'time' => $results['time'] ?? null,
                'title' => $results['title'] ?? null,
                'totaldisks' => $results['totaldisks'] ?? null,
                'totaltracks' => $results['totaltracks'] ?? null,
                'track' => $results['track'] ?? null,
                'year' => $results['year'] ?? null,
            ];

            $JSON[] = $objArray;
        } // end foreach

        if ($object) {
            $output["song_tag"] = $JSON;
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * videos
     *
     * This builds the JSON document for displaying video objects
     *
     * @param list<int|string> $objects Video id's to include
     * @param User $user
     * @param bool $encode
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "video"
     */
    public static function videos(array $objects, User $user, bool $encode = true, bool $object = true): array|string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_slice($objects, self::$offset, self::$limit);
        }
        $JSON = [];
        foreach ($objects as $video_id) {
            $video = new Video((int)$video_id);
            if ($video->isNew()) {
                continue;
            }
            $rating      = new Rating($video->id, 'video');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($video->id, 'video');
            $art_url     = Art::url($video->id, 'video', Core::get_request('auth'));
            $JSON[]      = [
                "id" => (string)$video->id,
                "title" => $video->title,
                "mime" => $video->mime,
                "resolution" => $video->get_f_resolution(),
                "size" => (int)$video->size,
                "genre" => self::genre_array($video->get_tags()),
                "time" => (int)$video->time,
                "url" => $video->play_url('', 'api', false, $user->getId(), $user->streamtoken),
                "art" => $art_url,
                "has_art" => $video->has_art(),
                "flag" => (bool)$flag->get_flag($user->getId()),
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "playcount" => (int)$video->total_count
            ];
        } // end foreach

        if ($encode) {
            if ($object) {
                $output["video"] = $JSON;
            } else {
                $output = $JSON[0] ?? [];
            }

            return json_encode($output, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * democratic
     *
     * This handles creating an JSON document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param list<array{
     *    object_type: LibraryItemEnum,
     *    object_id: int,
     *    track_id: int,
     *    track: int}> $object_ids Object IDs
     * @param User $user
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string
     */
    public static function democratic(array $object_ids, User $user, bool $object = true): string
    {
        if (!is_array($object_ids)) {
            $object_ids = [];
        }
        $democratic = Democratic::get_current_playlist($user);

        $JSON = [];
        foreach ($object_ids as $row_id => $data) {
            $className = ObjectTypeToClassNameMapper::map($data['object_type']->value);
            /** @var Song $song */
            $song = new $className($data['object_id']);
            if ($song->isNew()) {
                continue;
            }
            $song->fill_ext_info();

            $rating      = new Rating($song->id, 'song');
            $user_rating = $rating->get_user_rating($user->getId());
            $art_url     = Art::url($song->album, 'album', $_REQUEST['auth'] ?? '');
            $songType    = $song->type;
            $songMime    = $song->mime;
            $songBitrate = $song->bitrate;
            $play_url    = $song->play_url('', 'api', false, $user->id, $user->streamtoken);
            $song_album  = self::getAlbumRepository()->getNames($song->album);
            $song_artist = Artist::get_name_array_by_id($song->artist);

            $JSON[] = [
                "id" => (string)$song->id,
                "title" => $song->get_fullname(),
                "artist" => [
                    "id" => (string)$song->artist,
                    "name" => $song_artist['name'],
                    "prefix" => $song_artist['prefix'],
                    "basename" => $song_artist['basename']
                ],
                "album" => [
                    "id" => (string)$song->album,
                    "name" => $song_album['name'],
                    "prefix" => $song_album['prefix'],
                    "basename" => $song_album['basename']
                ],
                "genre" => self::genre_array($song->get_tags()),
                "track" => (int)$song->track,
                "time" => (int)$song->time,
                "format" => $songType,
                "bitrate" => $songBitrate,
                "mime" => $songMime,
                "url" => $play_url,
                "size" => (int)$song->size,
                "art" => $art_url,
                "has_art" => $song->has_art(),
                "rating" => $user_rating,
                "averagerating" => ($rating->get_average_rating() ?? null),
                "playcount" => (int)$song->total_count,
                "vote" => $democratic->get_vote($row_id)
            ];
        } // end foreach
        $output = ($object) ? ["song" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * user
     *
     * This handles creating an JSON document for a user
     */
    public static function user(User $user, bool $fullinfo, ?bool $object = true): string
    {
        $art_url = Art::url($user->id, 'user', $_REQUEST['auth'] ?? '');
        if ($fullinfo) {
            $JSON = [
                "id" => (string)$user->id,
                "username" => $user->username,
                "auth" => $user->apikey,
                "email" => $user->email,
                "access" => (int)$user->access,
                "streamtoken" => $user->streamtoken,
                "fullname_public" => (bool)$user->fullname_public,
                "validation" => $user->validation,
                "disabled" => (bool)$user->disabled,
                "create_date" => (int)$user->create_date,
                "last_seen" => (int)$user->last_seen,
                "website" => $user->website,
                "state" => $user->state,
                "city" => $user->city,
                "art" => $art_url,
                "has_art" => $user->has_art()
            ];
        } else {
            $JSON = [
                "id" => (string)$user->id,
                "username" => $user->username,
                "create_date" => $user->create_date,
                "last_seen" => $user->last_seen,
                "website" => $user->website,
                "state" => $user->state,
                "city" => $user->city,
                "art" => $art_url,
                "has_art" => $user->has_art()
            ];
        }
        if ($user->fullname_public) {
            $JSON['fullname'] = $user->fullname;
        }
        $output = ($object) ? ["user" => $JSON] : $JSON;

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * users
     *
     * This handles creating an JSON document for a user list
     *
     * @param list<int|string> $objects User id list
     * @param bool $encode return the array and don't json_encode the data
     * @param bool $object (whether to return as a named object array or regular array)
     * @return array|string JSON Object "label"
     */
    public static function users(array $objects, bool $encode = true, bool $object = true): array|string
    {
        $count  = self::$count ?? count($objects);
        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }

        $JSON = [];
        foreach ($objects as $user_id) {
            $user = new User((int)$user_id);
            if ($user->isNew()) {
                continue;
            }
            $JSON[] = [
                "id" => (string)$user_id,
                "username" => $user->username
            ];
        } // end foreach

        if ($encode) {
            if ($object) {
                $output = ["user" => $JSON];
            } else {
                $output = $JSON[0] ?? [];
            }

            return json_encode($output, JSON_PRETTY_PRINT) ?: '';
        }

        return $JSON;
    }

    /**
     * now_playing
     *
     * This handles creating an JSON document for a now_playing list
     *
     * @param list<array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int
     * }> $results
     */
    public static function now_playing(array $results): string
    {
        $JSON = [];
        foreach ($results as $now_playing) {
            $user = $now_playing['client'];
            if ($user->isNew()) {
                continue;
            }
            $media = $now_playing['media'];

            $JSON[] = [
                "id" => (string) $media->getId(),
                "type" => $media->getMediaType()->value,
                "client" => $now_playing['agent'],
                "expire" => (int) $now_playing['expire'],
                "user" => [
                    "id" => (string) $user->getId(),
                    "username" => $user->getUsername()
                ]
            ];
        }
        $output = ["now_playing" => $JSON];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * shouts
     *
     * This handles creating an JSON document for a shout list
     *
     * @param list<Shoutbox> $shouts Shout id list
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function shouts(array $shouts, bool $object = true): string
    {
        $JSON = [];

        foreach ($shouts as $shout) {
            $user = $shout->getUser();

            $JSON[] = [
                "id" => (string) $shout->getId(),
                "date" => $shout->getDate()->getTimestamp(),
                "text" => $shout->getText(),
                "object_type" => $shout->getObjectType(),
                "object_id" => $shout->getObjectId(),
                "user" => [
                    "id" => (string) ($user?->getId() ?? 0),
                    "username" => $user?->getUsername() ?? '',
                ]
            ];
        }
        $output = ($object) ? ["shout" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * timeline
     *
     * This handles creating an JSON document for an activity list
     *
     * @param int[] $activities Activity id list
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function timeline(array $activities, bool $object = true): string
    {
        $JSON = [];
        foreach ($activities as $activity_id) {
            $activity = new Useractivity($activity_id);
            $user     = new User($activity->user);
            $objArray = [
                "id" => (string)$activity_id,
                "date" => $activity->activity_date,
                "object_type" => $activity->object_type,
                "object_id" => (string)$activity->object_id,
                "action" => $activity->action,
                "user" => [
                    "id" => (string)$activity->user,
                    "username" => $user->username
                ]
            ];
            $JSON[] = $objArray;
        }
        $output = ($object) ? ["activity" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * deleted
     *
     * This handles creating a JSON document for deleted items
     *
     * @param string $object_type ('song', 'podcast_episode', 'video')
     * @param list<array{
     *     id: int,
     *     addition_time: int,
     *     delete_time: int,
     *     title: string,
     *     file: string,
     *     catalog: int,
     *     total_count: int,
     *     total_skip: int,
     *     update_time?: int,
     *     album?: int,
     *     artist?: int,
     *     podcast?: int,
     * }> $objects deleted object list
     */
    public static function deleted(string $object_type, array $objects): string
    {
        $count  = self::$count ?? count($objects);
        $output = [
            "total_count" => $count,
            "md5" => md5(serialize($objects)),
        ];

        if (($count > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $JSON = [];
        foreach ($objects as $row) {
            switch ($object_type) {
                case 'song':
                    if (isset($row['album']) && isset($row['artist']) && isset($row['update_time'])) {
                        $objArray = [
                            "id" => (string)$row['id'],
                            "addition_time" => $row['addition_time'],
                            "delete_time" => $row['delete_time'],
                            "title" => $row['title'],
                            "file" => $row['file'],
                            "catalog" => (string)$row['catalog'],
                            "total_count" => $row['total_count'],
                            "total_skip" => $row['total_skip'],
                            "update_time" => $row['update_time'],
                            "album" => (string)$row['album'],
                            "artist" => (string)$row['artist']
                        ];
                        $JSON[] = $objArray;
                    }
                    break;
                case 'podcast_episode':
                    if (isset($row['podcast'])) {
                        $objArray = [
                            "id" => (string)$row['id'],
                            "addition_time" => $row['addition_time'],
                            "delete_time" => $row['delete_time'],
                            "title" => $row['title'],
                            "file" => $row['file'],
                            "catalog" => (string)$row['catalog'],
                            "total_count" => $row['total_count'],
                            "total_skip" => $row['total_skip'],
                            "podcast" => (string)$row['podcast']
                        ];
                        $JSON[] = $objArray;
                    }
                    break;
                case 'video':
                    $objArray = [
                        "id" => (string)$row['id'],
                        "addition_time" => $row['addition_time'],
                        "delete_time" => $row['delete_time'],
                        "title" => $row['title'],
                        "file" => $row['file'],
                        "catalog" => (string)$row['catalog'],
                        "total_count" => $row['total_count'],
                        "total_skip" => $row['total_skip']
                    ];
                    $JSON[] = $objArray;
            }
        }
        $output["deleted_" . $object_type] = $JSON;

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getLicenseRepository(): LicenseRepositoryInterface
    {
        global $dic;

        return $dic->get(LicenseRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getBookmarkRepository(): BookmarkRepositoryInterface
    {
        global $dic;

        return $dic->get(BookmarkRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getLabelRepository(): LabelRepositoryInterface
    {
        global $dic;

        return $dic->get(LabelRepositoryInterface::class);
    }
}
