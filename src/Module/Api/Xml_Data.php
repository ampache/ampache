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
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;
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
use DOMDocument;

/**
 * Xml_Data Class
 *
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls
 */
class Xml_Data
{
    // This is added so that we don't pop any webservers
    private static ?int $limit  = 5000;
    private static int $offset  = 0;

    /**
     * set_offset
     *
     * This takes an int and changes the offset
     *
     * @param int $offset Change the starting position of your results. (e.g 5001 when selecting in groups of 5000)
     */
    public static function set_offset($offset): void
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
    public static function set_limit($limit): bool
    {
        if (!$limit) {
            return false;
        }

        self::$limit = (strtolower((string)$limit) == "none") ? null : (int)$limit;

        return true;
    }

    /**
     * error
     *
     * This generates a standard XML Error message
     * nothing fancy here...
     *
     * @param int|string $code Error code
     * @param string $string Error message
     * @param string $action
     * @param string $type
     */
    public static function error($code, $string, $action, $type): string
    {
        $xml_string = "\t<error errorCode=\"$code\">\n\t\t<errorAction><![CDATA[" . $action . "]]></errorAction>\n\t\t<errorType><![CDATA[" . $type . "]]></errorType>\n\t\t<errorMessage><![CDATA[" . $string . "]]></errorMessage>\n\t</error>";

        return self::output_xml($xml_string);
    }

    /**
     * success
     *
     * This generates a standard XML Success message
     * nothing fancy here...
     *
     * @param string $string success message
     * @param array $return_data
     */
    public static function success($string, $return_data = []): string
    {
        $xml_string = "\t<success code=\"1\">\n\t<message><![CDATA[" . $string . "]]></message></success>";
        foreach ($return_data as $title => $data) {
            $xml_string .= "\n\t<$title><![CDATA[" . $data . "]]></$title>";
        }

        return self::output_xml($xml_string);
    }

    /**
     * empty
     *
     * This generates an empty root element
     */
    public static function empty(): string
    {
        return "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset') . "\" ?>\n<root>\n</root>\n";
    }

    /**
     * genre_string
     *
     * This returns the formatted 'genre' string for an xml document
     * @param array $tags
     */
    private static function genre_string($tags): string
    {
        $string = '';

        if (!empty($tags)) {
            $atags = [];
            foreach ($tags as $tag) {
                if (array_key_exists($tag['id'], $atags)) {
                    $atags[$tag['id']]['count']++;
                } else {
                    $atags[$tag['id']] = [
                        'name' => $tag['name'],
                        'count' => 1
                    ];
                }
            }

            foreach ($atags as $tag_id => $data) {
                $string .= "\t<genre id=\"" . $tag_id . "\">\t<name><![CDATA[" . $data['name'] . "]]></name></genre>\n";
            }
        }

        return $string;
    }

    /**
     * output_xml_from_array
     * This takes a one dimensional array and creates a XML document from it. For
     * use primarily by the ajax mojo.
     * @param array $array
     * @param bool $callback
     * @param string $type
     */
    public static function output_xml_from_array($array, $callback = false, $type = ''): string
    {
        $string = '';

        // If we weren't passed an array then return
        if (!is_array($array)) {
            return $string;
        }

        // The type is used for the different XML docs we pass
        switch ($type) {
            case 'itunes':
                foreach ($array as $key => $value) {
                    if (is_array($value)) {
                        $value = xoutput_from_array($value, true, $type);
                        $string .= "\t\t<$key>\n$value\t\t</$key>\n";
                    } elseif ($key == "key") {
                        $string .= "\t\t<$key>$value</$key>\n";
                    } elseif (is_int($value)) {
                        $string .= "\t\t\t<key>$key</key><integer>$value</integer>\n";
                    } elseif ($key == "Date Added") {
                        $string .= "\t\t\t<key>$key</key><date>$value</date>\n";
                    } elseif (is_string($value)) {
                        /* We need to escape the value */
                        $string .= "\t\t\t<key>$key</key><string><![CDATA[" . $value . "]]></string>\n";
                    }
                } // end foreach

                return $string;
            case 'xspf':
                foreach ($array as $key => $value) {
                    if (is_array($value)) {
                        $value = xoutput_from_array($value, true, $type);
                        $string .= "\t\t<$key>\n$value\t\t</$key>\n";
                    } elseif ($key == "key") {
                        $string .= "\t\t<$key>$value</$key>\n";
                    } elseif (is_numeric($value)) {
                        $string .= "\t\t\t<$key>$value</$key>\n";
                    } elseif (is_string($value)) {
                        /* We need to escape the value */
                        $string .= "\t\t\t<$key><![CDATA[" . $value . "]]></$key>\n";
                    }
                } // end foreach

                return $string;
            default:
                foreach ($array as $key => $value) {
                    // No numeric keys
                    if (is_numeric($key)) {
                        $key = 'item';
                    }

                    if (is_array($value)) {
                        // Call ourself
                        $value = xoutput_from_array($value, true);
                        $string .= "\t<content div=\"$key\">$value</content>\n";
                    } else {
                        /* We need to escape the value */
                        $string .= "\t<content div=\"$key\"><![CDATA[" . $value . "]]></content>\n";
                    }
                    // end foreach elements
                }
                if (!$callback) {
                    $string = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<root>\n" . $string . "</root>\n";
                }

                return Ui::clean_utf8($string);
        }
    }

    /**
     * keyed_array
     *
     * This will build an xml document from a key'd array,
     *
     * @param array $array keyed array of objects (key => value, key => value)
     * @param bool $callback (don't output xml when true)
     * @param string|bool $object
     */
    public static function keyed_array($array, $callback = false, $object = false): string
    {
        $string = '';
        // Foreach it
        foreach ($array as $key => $value) {
            $attribute = '';
            // See if the key has attributes
            if (is_array($value) && isset($value['attributes'])) {
                $attribute = ' ' . $value['attributes'];
                $key       = $value['value'];
            }

            // If it's an array, run again
            if (is_array($value)) {
                $value = self::keyed_array($value, true);
                $string .= ($object) ? "<$object>\n$value\n</$object>\n" : "<$key$attribute>\n$value\n</$key>\n";
            } else {
                $string .= ($object) ? "\t<$object index=\"" . $key . "\"><![CDATA[" . $value . "]]></$object>\n" : "\t<$key$attribute><![CDATA[" . $value . "]]></$key>\n";
            }
        } // end foreach

        if (!$callback) {
            $string = self::output_xml($string);
        }

        return $string;
    }

    /**
     * object_array
     *
     * This will build an xml document from an array of arrays, an id is required for the array data
     * <root>
     *   <$object_type> //optional
     *     <$item id="123">
     *       <data></data>
     *
     * @param array $array
     * @param string $item
     * @param string $object_type
     */
    public static function object_array($array, $item, $object_type = ''): string
    {
        $string = ($object_type == '') ? '' : "<$object_type>\n";
        // Foreach it
        foreach ($array as $object) {
            $string .= "\t<$item id=\"" . $object['id'] . "\">\n";
            foreach ($object as $name => $value) {
                $filter = (is_numeric($value)) ? $value : "<![CDATA[" . $value . "]]>";
                $string .= ($name !== 'id') ? "\t\t<$name>$filter</$name>\n" : '';
            }
            $string .= "\t</$item>\n";
        } // end foreach
        $string .= ($object_type == '') ? '' : "</$object_type>";

        return self::output_xml($string);
    }

    /**
     * indexes
     *
     * This takes an array of object_ids and return XML based on the type of object
     * we want
     *
     * @param list<int> $objects Array of object_ids (Mixed string|int)
     * @param string $object_type 'album_artist'|'album'|'artist'|'catalog'|'live_stream'|'playlist'|'podcast_episode'|'podcast'|'share'|'song_artist'|'song'|'video'
     * @param User $user
     * @param bool $include include children from objects that have them
     */
    public static function index($objects, $object_type, $user, $include = false): string
    {
        if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $string = "<total_count>" . count($objects) . "</total_count>\n";

        switch ($object_type) {
            case 'album_artist':
                foreach ($objects as $object_id) {
                    if ($include) {
                        $string .= "<artist id=\"" . $object_id . "\">\n";
                        $sql        = "SELECT DISTINCT `album_map`.`album_id` FROM `album_map` WHERE `album_map`.`object_id` = ? AND `album_map`.`object_type` = 'album';";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $string .= "<album id=\"" . $row['album_id'] . "\"/>\n";
                        }
                        $string .= "</artist>\n";
                    } else {
                        $string .= "<artist id=\"" . $object_id . "\"/>\n";
                    }
                }
                break;
            case 'song_artist':
                foreach ($objects as $object_id) {
                    if ($include) {
                        $string .= "<artist id=\"" . $object_id . "\">\n";
                        $sql        = "SELECT DISTINCT `album_map`.`album_id` FROM `album_map` WHERE `album_map`.`object_id` = ? AND `album_map`.`object_type` = 'song';";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $string .= "<album id=\"" . $row['album_id'] . "\"/>\n";
                        }
                        $string .= "</artist>\n";
                    } else {
                        $string .= "<artist id=\"" . $object_id . "\"/>\n";
                    }
                }
                break;
            case 'artist':
                foreach ($objects as $object_id) {
                    if ($include) {
                        $string .= "<artist id=\"" . $object_id . "\">\n";
                        $sql        = "SELECT DISTINCT `album_map`.`album_id` FROM `album_map` WHERE `album_map`.`object_id` = ?;";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $string .= "<album id=\"" . $row['album_id'] . "\"/>\n";
                        }
                        $string .= "</artist>\n";
                    } else {
                        $string .= "<artist id=\"" . $object_id . "\"/>\n";
                    }
                }
                break;
            case 'album':
                foreach ($objects as $object_id) {
                    if ($include) {
                        $string .= "<album id=\"" . $object_id . "\">\n";
                        $sql        = "SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` = ?;";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $string .= "<song id=\"" . $row['id'] . "\"/>\n";
                        }
                        $string .= "</album>\n";
                    } else {
                        $string .= "<album id=\"" . $object_id . "\"/>\n";
                    }
                }
                break;
            case 'playlist':
                foreach ($objects as $object_id) {
                    if ($include) {
                        $string .= "<playlist id=\"" . $object_id . "\">\n";
                        /**
                         * Strip smart_ from playlist id and compare to original
                         * smartlist = 'smart_1'
                         * playlist  = 1000000
                         */
                        if ((int)$object_id === 0) {
                            $playlist = new Search((int) str_replace('smart_', '', (string)$object_id), 'song', $user);
                            foreach ($playlist->get_items() as $song) {
                                $string .= "<song id=\"" . $song['object_id'] . "\"></song>\n";
                            }
                        } else {
                            $sql        = "SELECT `playlist_data`.`id`, `playlist_data`.`object_id`, `playlist_data`.`object_type` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? ORDER BY `playlist_data`.`track`;";
                            $db_results = Dba::read($sql, [$object_id]);
                            while ($row = Dba::fetch_assoc($db_results)) {
                                $string .= "<" . $row['object_type'] . " id=\"" . $row['object_id'] . "\"></" . $row['object_type'] . ">\n";
                            }
                        }
                        $string .= "</playlist>\n";
                    } else {
                        $string .= "<playlist id=\"" . $object_id . "\"/>\n";
                    }
                }
                break;
            case 'podcast':
                foreach ($objects as $object_id) {
                    if ($include) {
                        $string .= "<podcast id=\"" . $object_id . "\">\n";
                        $sql        = "SELECT DISTINCT `podcast_episode`.`id` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ?;";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $string .= "<podcast_episode id=\"" . $row['id'] . "\"></podcast_episode>\n";
                        }
                        $string .= "</podcast>\n";
                    } else {
                        $string .= "<podcast id=\"" . $object_id . "\"/>\n";
                    }
                }
                break;
            case 'catalog':
            case 'live_stream':
            case 'podcast_episode':
            case 'share':
            case 'song':
            case 'video':
                foreach ($objects as $object_id) {
                    $string .= "<$object_type id=\"" . $object_id . "\"/>\n";
                } // end foreach objects
                break;
        }

        return self::output_xml($string);
    }

    /**
     * indexes
     *
     * This takes an array of object_ids and return XML based on the type of object
     * we want
     *
     * @param array $objects Array of object_ids (Mixed string|int)
     * @param string $object_type 'album_artist'|'album'|'artist'|'catalog'|'live_stream'|'playlist'|'podcast_episode'|'podcast'|'share'|'song_artist'|'song'|'video'
     * @param User $user
     * @param bool $full_xml whether to return a full XML document or just the node.
     * @param bool $include include episodes from podcasts or tracks in a playlist
     */
    public static function indexes($objects, $object_type, $user, $full_xml = true, $include = false): string
    {
        if ((count($objects) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        // you might not want the joined tables for playlists
        $total_count = (AmpConfig::get('hide_search', false) && $object_type == 'playlist')
            ? Catalog::get_update_info('search', $user->id) + Catalog::get_update_info('playlist', $user->id)
            : Catalog::get_update_info($object_type, $user->id);
        $string = ($full_xml) ? "<total_count>" . $total_count . "</total_count>\n" : '';

        // here is where we call the object type
        foreach ($objects as $object_id) {
            switch ($object_type) {
                case 'catalog':
                    $string .= self::catalogs($objects, $user);
                    break;
                case 'album_artist':
                case 'artist':
                case 'song_artist':
                    if ($include) {
                        $string .= self::artists([$object_id], ['songs', 'albums'], $user, false);
                    } else {
                        $artist = new Artist($object_id);
                        if ($artist->isNew()) {
                            break;
                        }
                        $albums = static::getAlbumRepository()->getAlbumByArtist($object_id);
                        $string .= "<artist id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $artist->get_fullname() . "]]></name>\n\t<prefix><![CDATA[" . $artist->prefix . "]]></prefix>\n\t<basename><![CDATA[" . $artist->name . "]]></basename>\n";
                        foreach ($albums as $album_id) {
                            if ($album_id > 0) {
                                $album = new Album($album_id);
                                $string .= "\t<album id=\"" . $album_id . "\">\t<name><![CDATA[" . $album->get_fullname() . "]]></name>\n\t<prefix><![CDATA[" . $album->prefix . "]]></prefix>\n\t<basename><![CDATA[" . $album->name . "]]></basename>\n\t</album>\n";
                            }
                        }
                        $string .= "</artist>\n";
                    }
                    break;
                case 'album':
                    if ($include) {
                        $string .= self::albums([$object_id], ['songs'], $user, false);
                    } else {
                        $album = new Album($object_id);
                        $string .= "<$object_type id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $album->get_fullname() . "]]></name>\n\t<prefix><![CDATA[" . $album->prefix . "]]></prefix>\n\t<basename><![CDATA[" . $album->name . "]]></basename>\n";
                        if ($album->get_artist_fullname() != "") {
                            $album_artist = [
                                "id" => $album->album_artist,
                                "name" => $album->f_artist_name,
                                "prefix" => $album->artist_prefix,
                                "basename" => $album->artist_name
                            ];
                            $string .= "\t<artist id=\"" . $album_artist['id'] . "\">\t<name><![CDATA[" . $album_artist['name'] . "]]></name>\n\t<prefix><![CDATA[" . $album_artist['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $album_artist['basename'] . "]]></basename>\n</artist>\n";
                        }
                        $string .= "</$object_type>\n";
                    }
                    break;
                case 'song':
                    $song        = new Song($object_id);
                    $song_album  = self::getAlbumRepository()->getNames($song->album);
                    $song_artist = Artist::get_name_array_by_id($song->artist);
                    $string .= "<$object_type id=\"" . $object_id . "\">\n\t<title><![CDATA[" . $song->get_fullname() . "]]></title>\n\t<name><![CDATA[" . $song->get_fullname() . "]]></name>\n" .
                        "\t<artist id=\"" . $song->artist . "\"><name><![CDATA[" . $song_artist['name'] . "]]></name><prefix><![CDATA[" . $song_artist['prefix'] . "]]></prefix><basename><![CDATA[" . $song_artist['basename'] . "]]></basename></artist>\n" .
                        "\t<album id=\"" . $song->album . "\"><name><![CDATA[" . $song_album['name'] . "]]></name>\n\t<prefix><![CDATA[" . $song_album['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $song_album['basename'] . "]]></basename>\n</album>\n";
                    if ($song->get_album_artist_fullname() != "") {
                        $album_artist = ($song->artist !== $song->albumartist)
                            ? Artist::get_name_array_by_id($song->albumartist)
                            : $song_artist;
                        $string .= "\t<albumartist id=\"" . $song->albumartist . "\"><name><![CDATA[" . $album_artist['name'] . "]]></name>\n\t<prefix><![CDATA[" . $album_artist['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $album_artist['basename'] . "]]></basename>\n</albumartist>\n";
                    }
                    $string .= "\t<disk><![CDATA[" . $song->disk . "]]></disk>\n\t<track>" . $song->track . "</track>\n</$object_type>\n";
                    break;
                case 'playlist':
                    if ((int)$object_id === 0) {
                        $playlist       = new Search((int) str_replace('smart_', '', (string)$object_id), 'song', $user);
                        $playitem_total = $playlist->last_count;
                    } else {
                        $playlist       = new Playlist($object_id);
                        $playitem_total = $playlist->get_media_count('song');
                    }
                    $playlist_name = $playlist->get_fullname();
                    $playlist_user = $playlist->username;

                    $songs = ($include) ? $playlist->get_items() : [];
                    $string .= "<$object_type id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $playlist_name . "]]></name>\n\t<items>" . (int)$playitem_total . "</items>\n\t<owner><![CDATA[" . $playlist_user . "]]></owner>\n\t<type><![CDATA[" . $playlist->type . "]]></type>\n";
                    foreach ($songs as $song_id) {
                        if ($song_id['object_type'] === LibraryItemEnum::SONG) {
                            $string .= "\t\t<playlisttrack id=\"" . $song_id['object_id'] . "\">" . $song_id['track'] . "</playlisttrack>\n";
                        }
                    }
                    $string .= "</$object_type>\n";
                    break;
                case 'share':
                    $string .= self::shares($objects, $user);
                    break;
                case 'podcast':
                    $podcast = self::getPodcastRepository()->findById($object_id);

                    if ($podcast !== null) {
                        $string .= "<podcast id=\"$object_id\">\n\t<name><![CDATA[" . $podcast->get_fullname() . "]]></name>\n\t<description><![CDATA[" . $podcast->get_description() . "]]></description>\n\t<language><![CDATA[" . scrub_out($podcast->getLanguage()) . "]]></language>\n\t<copyright><![CDATA[" . scrub_out($podcast->getCopyright()) . "]]></copyright>\n\t<feed_url><![CDATA[" . $podcast->getFeedUrl() . "]]></feed_url>\n\t<generator><![CDATA[" . scrub_out($podcast->getGenerator()) . "]]></generator>\n\t<website><![CDATA[" . scrub_out($podcast->getWebsite()) . "]]></website>\n\t<build_date><![CDATA[" . $podcast->getLastBuildDate()->format(DATE_ATOM) . "]]></build_date>\n\t<sync_date><![CDATA[" . $podcast->getLastSyncDate()->format(DATE_ATOM) . "]]></sync_date>\n\t<public_url><![CDATA[" . $podcast->get_link() . "]]></public_url>\n";
                        if ($include) {
                            $episodes = $podcast->getEpisodeIds();
                            foreach ($episodes as $episode_id) {
                                $string .= self::podcast_episodes([$episode_id], $user, false);
                            }
                        }
                        $string .= "\t</podcast>\n";
                    }
                    break;
                case 'podcast_episode':
                    $string .= self::podcast_episodes($objects, $user);
                    break;
                case 'video':
                    $string .= self::videos($objects, $user);
                    break;
                case 'live_stream':
                    $string .= self::live_streams($objects, $user);
            }
        } // end foreach objects

        return self::output_xml($string, $full_xml);
    }

    /**
     * searches
     *
     * This takes an array of object_ids and return XML based on the type of object
     * we want
     *
     * @param array $searches Array of object_ids (Mixed string|int)
     * @param User $user
     */
    public static function searches($searches, $user): string
    {
        $string = "<search>\n";

        // here is where we call the object type
        foreach ($searches as $object_type => $objects) {
            switch ($object_type) {
                case 'artist':
                    if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
                        $objects = array_splice($objects, self::$offset, self::$limit);
                    }
                    foreach ($objects as $object_id) {
                        $artist = new Artist($object_id);
                        if ($artist->isNew()) {
                            break;
                        }
                        $string .= "<$object_type id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $artist->get_fullname() . "]]></name>\n\t<prefix><![CDATA[" . $artist->prefix . "]]></prefix>\n\t<basename><![CDATA[" . $artist->name . "]]></basename>\n</$object_type>\n";
                    }
                    break;
                case 'album':
                    if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
                        $objects = array_splice($objects, self::$offset, self::$limit);
                    }
                    foreach ($objects as $object_id) {
                        $album = new Album($object_id);
                        $string .= "<$object_type id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $album->get_fullname() . "]]></name>\n\t<prefix><![CDATA[" . $album->prefix . "]]></prefix>\n\t<basename><![CDATA[" . $album->name . "]]></basename>\n";
                        if ($album->get_artist_fullname() != "") {
                            $album_artist = [
                                "id" => $album->album_artist,
                                "name" => $album->f_artist_name,
                                "prefix" => $album->artist_prefix,
                                "basename" => $album->artist_name
                            ];
                            $string .= "\t<artist id=\"" . $album_artist['id'] . "\">\t<name><![CDATA[" . $album_artist['name'] . "]]></name>\n\t<prefix><![CDATA[" . $album_artist['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $album_artist['basename'] . "]]></basename>\n</artist>\n";
                        }
                        $string .= "</$object_type>\n";
                    }
                    break;
                case 'song':
                    if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
                        $objects = array_splice($objects, self::$offset, self::$limit);
                    }
                    foreach ($objects as $object_id) {
                        $song        = new Song($object_id);
                        $song_album  = self::getAlbumRepository()->getNames($song->album);
                        $song_artist = Artist::get_name_array_by_id($song->artist);
                        $string .= "<$object_type id=\"" . $object_id . "\">\n\t<title><![CDATA[" . $song->get_fullname() . "]]></title>\n\t<name><![CDATA[" . $song->get_fullname() . "]]></name>\n" .
                            "\t<artist id=\"" . $song->artist . "\"><name><![CDATA[" . $song_artist['name'] . "]]></name><prefix><![CDATA[" . $song_artist['prefix'] . "]]></prefix><basename><![CDATA[" . $song_artist['basename'] . "]]></basename></artist>\n" .
                            "\t<album id=\"" . $song->album . "\"><name><![CDATA[" . $song_album['name'] . "]]></name>\n\t<prefix><![CDATA[" . $song_album['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $song_album['basename'] . "]]></basename>\n</album>\n";
                        if ($song->get_album_artist_fullname() != "") {
                            $album_artist = ($song->artist !== $song->albumartist)
                                ? Artist::get_name_array_by_id($song->albumartist)
                                : $song_artist;
                            $string .= "\t<albumartist id=\"" . $song->albumartist . "\"><name><![CDATA[" . $album_artist['name'] . "]]></name>\n\t<prefix><![CDATA[" . $album_artist['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $album_artist['basename'] . "]]></basename>\n</albumartist>\n";
                        }
                        $string .= "\t<disk><![CDATA[" . $song->disk . "]]></disk>\n\t<track>" . $song->track . "</track>\n</$object_type>\n";
                    }
                    break;
                case 'playlist':
                    if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
                        $objects = array_splice($objects, self::$offset, self::$limit);
                    }
                    foreach ($objects as $object_id) {
                        if ((int)$object_id === 0) {
                            $playlist       = new Search((int)str_replace('smart_', '', (string)$object_id), 'song', $user);
                            $playitem_total = $playlist->last_count;
                        } else {
                            $playlist       = new Playlist($object_id);
                            $playitem_total = $playlist->get_media_count('song');
                        }
                        $playlist_name = $playlist->get_fullname();
                        $playlist_user = $playlist->username;

                        $string .= "<$object_type id=\"" . $object_id . "\">\n\t<name><![CDATA[" . $playlist_name . "]]></name>\n\t<items>" . (int)$playitem_total . "</items>\n\t<owner><![CDATA[" . $playlist_user . "]]></owner>\n\t<type><![CDATA[" . $playlist->type . "]]></type>\n</$object_type>\n";
                    }
                    break;
                case 'share':
                    $string .= self::shares($objects, $user);
                    break;
                case 'podcast':
                    if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
                        $objects = array_splice($objects, self::$offset, self::$limit);
                    }
                    foreach ($objects as $object_id) {
                        $podcast = self::getPodcastRepository()->findById($object_id);
                        if ($podcast !== null) {
                            $string .= "<podcast id=\"$object_id\">\n\t<name><![CDATA[" . $podcast->get_fullname() . "]]></name>\n\t<description><![CDATA[" . $podcast->get_description() . "]]></description>\n\t<language><![CDATA[" . scrub_out($podcast->getLanguage()) . "]]></language>\n\t<copyright><![CDATA[" . scrub_out($podcast->getCopyright()) . "]]></copyright>\n\t<feed_url><![CDATA[" . $podcast->getFeedUrl() . "]]></feed_url>\n\t<generator><![CDATA[" . scrub_out($podcast->getGenerator()) . "]]></generator>\n\t<website><![CDATA[" . scrub_out($podcast->getWebsite()) . "]]></website>\n\t<build_date><![CDATA[" . $podcast->getLastBuildDate()->format(DATE_ATOM) . "]]></build_date>\n\t<sync_date><![CDATA[" . $podcast->getLastSyncDate()->format(DATE_ATOM) . "]]></sync_date>\n\t<public_url><![CDATA[" . $podcast->get_link() . "]]></public_url>\n\t</podcast>\n";
                        }
                    }
                    break;
                case 'podcast_episode':
                    if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
                        $objects = array_splice($objects, self::$offset, self::$limit);
                    }
                    $string .= self::podcast_episodes($objects, $user, false);
                    break;
                case 'video':
                    $string .= self::videos($objects, $user, false);
                    break;
                case 'live_stream':
                    $string .= self::live_streams($objects, $user, false);
            }
        } // end foreach objects
        $string .= "</search>";

        return self::output_xml($string);
    }

    /**
     * lists
     *
     * This takes a name array of objects and return the data in XML format
     *
     * @param array $objects Array of object_ids array("id" => 1, "name" => 'Artist Name')
     */
    public static function lists($objects): string
    {
        $string = "<total_count>" . count($objects) . "</total_count>\n";
        if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_slice($objects, self::$offset, self::$limit);
        }

        $pattern = '/^(' . implode('\\s|', explode('|', AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La'))) . '\\s)(.*)/i';
        foreach ($objects as $object) {
            $trimmed  = Catalog::trim_prefix(trim((string)$object['name']), $pattern);
            $prefix   = $trimmed['prefix'];
            $basename = $trimmed['string'];
            $string .= "<list id=\"" . $object['id'] . "\">\n" .
                "\t<name><![CDATA[" . $object['name'] . "]]></name>\n" .
                "\t<prefix><![CDATA[" . $prefix . "]]></prefix>\n" .
                "\t<basename><![CDATA[" . $basename . "]]></basename>\n</list>\n";
        } // end foreach objects

        return self::output_xml($string);
    }

    /**
     * browses
     *
     * This takes a name array of objects and return the data in XML format
     *
     * @param array $objects Array of object_ids array("id" => 1, "name" => 'Artist Name')
     * @param int|null $parent_id
     * @param string $parent_type
     * @param string $child_type
     * @param int|null $catalog_id
     */
    public static function browses($objects, $parent_id, $parent_type, $child_type, $catalog_id): string
    {
        $string = "<total_count>" . count($objects) . "</total_count>\n";
        if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_slice($objects, self::$offset, self::$limit);
        }
        $string .= "<catalog_id>" . $catalog_id . "</catalog_id>\n" .
            "<parent_id>" . $parent_id . "</parent_id>\n" .
            "<parent_type>" . $parent_type . "</parent_type>\n" .
            "<child_type>" . $child_type . "</child_type>\n";

        $pattern = '/^(' . implode('\\s|', explode('|', AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La'))) . '\\s)(.*)/i';
        foreach ($objects as $object) {
            $trimmed  = Catalog::trim_prefix(trim((string)$object['name']), $pattern);
            $prefix   = $trimmed['prefix'];
            $basename = $trimmed['string'];
            $string .= "<browse id=\"" . $object['id'] . "\">\n" .
                "\t<name><![CDATA[" . $object['name'] . "]]></name>\n" .
                "\t<prefix><![CDATA[" . $prefix . "]]></prefix>\n" .
                "\t<basename><![CDATA[" . $basename . "]]></basename>\n</browse>\n";
        } // end foreach objects

        return self::output_xml($string);
    }

    /**
     * licenses
     *
     * This returns licenses to the user, in a pretty xml document with the information
     *
     * @param int[] $licenses Licence id's assigned to songs and artists
     * @param User $user
     */
    public static function licenses($licenses, $user): string
    {
        if ((count($licenses) > self::$limit || self::$offset > 0) && self::$limit) {
            $licenses = array_splice($licenses, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_update_info('license', $user->id) . "</total_count>\n";

        $licenseRepository = self::getLicenseRepository();

        foreach ($licenses as $license_id) {
            $license = $licenseRepository->findById($license_id);
            if ($license !== null) {
                $string .= "<license id=\"$license_id\">\n\t<name><![CDATA[" . $license->getName() . "]]></name>\n\t<description><![CDATA[" . $license->getDescription() . "]]></description>\n\t<external_link><![CDATA[" . $license->getLinkFormatted() . "]]></external_link>\n</license>\n";
            }
        }

        return self::output_xml($string);
    }

    /**
     * labels
     *
     * This returns labels to the user, in a pretty xml document with the information
     *
     * @param int[] $labels
     * @param User $user
     */
    public static function labels($labels, $user): string
    {
        if ((count($labels) > self::$limit || self::$offset > 0) && self::$limit) {
            $labels = array_splice($labels, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_update_info('license', $user->id) . "</total_count>\n";

        $labelRepository = self::getLabelRepository();

        foreach ($labels as $label_id) {
            $label = $labelRepository->findById($label_id);
            if ($label === null) {
                continue;
            }
            $label->format();

            $string .= "<license id=\"$label_id\">\n\t<name><![CDATA[" . $label->get_fullname() . "]]></name>\n\t<artists><![CDATA[" . $label->artist_count . "]]></artists>\n\t<summary><![CDATA[" . $label->summary . "]]></summary>\n\t<external_link><![CDATA[" . $label->get_link() . "]]></external_link>\n\t<address><![CDATA[" . $label->address . "]]></address>\n\t<category><![CDATA[" . $label->category . "]]></category>\n\t<email><![CDATA[" . $label->email . "]]></email>\n\t<website><![CDATA[" . $label->website . "]]></website>\n\t<user><![CDATA[" . $label->user . "]]></user>\n</license>\n";
        } // end foreach

        return self::output_xml($string);
    }

    /**
     * live_streams
     *
     * This returns live_streams to the user, in a pretty xml document with the information
     *
     * @param int[] $live_streams
     * @param User $user
     * @param bool $full_xml
     */
    public static function live_streams($live_streams, $user, $full_xml = true): string
    {
        if ((count($live_streams) > self::$limit || self::$offset > 0) && self::$limit) {
            $live_streams = array_splice($live_streams, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('live_stream', $user->id) . "</total_count>\n" : '';

        foreach ($live_streams as $live_stream_id) {
            $live_stream = new Live_Stream($live_stream_id);

            $string .= "<live_stream id=\"" . $live_stream_id . "\">\n\t<name><![CDATA[" . $live_stream->get_fullname() . "]]></name>\n\t<url><![CDATA[" . $live_stream->url . "]]></url>\n\t<codec><![CDATA[" . $live_stream->codec . "]]></codec>\n\t<catalog>" . $live_stream->catalog . "</catalog>\n\t<site_url><![CDATA[" . $live_stream->site_url . "]]></site_url>\n</live_stream>\n";
        } // end foreach

        return self::output_xml($string, $full_xml);
    }

    /**
     * genres
     *
     * This returns genres to the user, in a pretty xml document with the information
     *
     * @param int[] $tags Genre id's to include
     * @param User $user
     */
    public static function genres($tags, $user): string
    {
        if ((count($tags) > self::$limit || self::$offset > 0) && self::$limit) {
            $tags = array_splice($tags, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_update_info('tag', $user->id) . "</total_count>\n";

        foreach ($tags as $tag_id) {
            $tag    = new Tag($tag_id);
            $counts = $tag->count();
            $string .= "<genre id=\"$tag_id\">\n\t<name><![CDATA[" . $tag->name . "]]></name>\n\t<albums>" . (int) ($counts['album'] ?? 0) . "</albums>\n\t<artists>" . (int) ($counts['artist'] ?? 0) . "</artists>\n\t<songs>" . (int) ($counts['song'] ?? 0) . "</songs>\n\t<videos>" . (int) ($counts['video'] ?? 0) . "</videos>\n\t<playlists>" . (int) ($counts['playlist'] ?? 0) . "</playlists>\n\t<live_streams>" . (int) ($counts['live_stream'] ?? 0) . "</live_streams>\n</genre>\n";
        } // end foreach

        return self::output_xml($string);
    }

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty xml document with the information
     * we want
     *
     * @param int[] $artists Artist id's to include
     * @param array $include Array of other items to include.
     * @param User $user
     * @param bool $full_xml whether to return a full XML document or just the node.
     */
    public static function artists($artists, $include, $user, $full_xml = true): string
    {
        if ((count($artists) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $artists = array_splice($artists, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('artist', $user->id) . "</total_count>\n" : '';

        Rating::build_cache('artist', $artists);

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            if ($artist->isNew()) {
                continue;
            }
            $artist->format();

            $rating      = new Rating($artist_id, 'artist');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($artist_id, 'artist');
            $tag_string  = self::genre_string($artist->tags);

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $artist_id . '&object_type=artist';

            // Handle includes
            $albums = (in_array("albums", $include)) ? self::albums(static::getAlbumRepository()->getAlbumByArtist($artist_id), [], $user, false) : '';
            $songs  = (in_array("songs", $include)) ? self::songs(static::getSongRepository()->getByArtist($artist_id), $user, false) : '';

            $string .= "<artist id=\"" . $artist->id . "\">\n\t<name><![CDATA[" . $artist->get_fullname() . "]]></name>\n\t<prefix><![CDATA[" . $artist->prefix . "]]></prefix>\n\t<basename><![CDATA[" . $artist->name . "]]></basename>\n" . $tag_string . "\t<albums>" . $albums . "</albums>\n\t<albumcount>" . $artist->album_count . "</albumcount>\n\t<songs>" . $songs . "</songs>\n\t<songcount>" . $artist->song_count . "</songcount>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<has_art>" . ($artist->has_art() ? 1 : 0) . "</has_art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . (string)$rating->get_average_rating() . "</averagerating>\n\t<mbid><![CDATA[" . $artist->mbid . "]]></mbid>\n\t<summary><![CDATA[" . $artist->summary . "]]></summary>\n\t<time><![CDATA[" . $artist->time . "]]></time>\n\t<yearformed>" . (int)$artist->yearformed . "</yearformed>\n\t<placeformed><![CDATA[" . $artist->placeformed . "]]></placeformed>\n</artist>\n";
        } // end foreach artists

        return self::output_xml($string, $full_xml);
    }

    /**
     * albums
     *
     * This echos out a standard albums XML document, it pays attention to the limit
     *
     * @param int[] $albums Album id's to include
     * @param array|false $include Array of other items to include.
     * @param User $user
     * @param bool $full_xml whether to return a full XML document or just the node.
     */
    public static function albums($albums, $include, $user, $full_xml = true): string
    {
        if ((count($albums) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $albums = array_splice($albums, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('album', $user->id) . "</total_count>\n" : '';
        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');

        Rating::build_cache('album', $albums);

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            if ($album->isNew()) {
                continue;
            }
            $album->format();
            $album_artists = [];
            foreach ($album->get_artists() as $artist_id) {
                $album_artists[] = Artist::get_name_array_by_id($artist_id);
            }
            $song_artists = [];
            foreach ($album->get_song_artists() as $artist_id) {
                $song_artists[] = Artist::get_name_array_by_id($artist_id);
            }

            $rating      = new Rating($album_id, 'album');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($album_id, 'album');
            $year        = ($original_year && $album->original_year)
                ? $album->original_year
                : $album->year;

            $string .= "<album id=\"" . $album->id . "\">\n\t<name><![CDATA[" . $album->get_fullname() . "]]></name>\n\t<prefix><![CDATA[" . $album->prefix . "]]></prefix>\n\t<basename><![CDATA[" . $album->name . "]]></basename>\n";
            if ($album->get_artist_fullname() != "") {
                foreach ($album_artists as $album_artist) {
                    $string .= "\t<artist id=\"" . $album_artist['id'] . "\"><name><![CDATA[" . $album_artist['name'] . "]]></name>\n\t<prefix><![CDATA[" . $album_artist['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $album_artist['basename'] . "]]></basename>\n</artist>\n";
                }
            }
            foreach ($song_artists as $song_artist) {
                $string .= "\t<songartist id=\"" . $song_artist['id'] . "\"><name><![CDATA[" . $song_artist['name'] . "]]></name>\n\t<prefix><![CDATA[" . $song_artist['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $song_artist['basename'] . "]]></basename>\n</songartist>\n";
            }
            // Handle includes
            $songs = ($include && in_array("songs", $include)) ? self::songs(static::getSongRepository()->getByAlbum($album->id), $user, false) : '';

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $album->id . '&object_type=album';
            $string .= "\t<time>" . $album->total_duration . "</time>\n\t<year>" . $year . "</year>\n\t<tracks>" . $songs . "</tracks>\n\t<songcount>" . $album->song_count . "</songcount>\n\t<diskcount>" . $album->disk_count . "</diskcount>\n\t<type>" . $album->release_type . "</type>\n" . self::genre_string($album->tags) . "\t<art><![CDATA[" . $art_url . "]]></art>\n\t<has_art>" . ($album->has_art() ? 1 : 0) . "</has_art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n\t<mbid><![CDATA[" . $album->mbid . "]]></mbid>\n</album>\n";
        } // end foreach

        return self::output_xml($string, $full_xml);
    }

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty XML document
     *
     * @param array $playlists Playlist id's to include
     * @param User $user
     * @param bool $songs
     */
    public static function playlists($playlists, $user, $songs = false): string
    {
        if ((count($playlists) > self::$limit || self::$offset > 0) && self::$limit) {
            $playlists = array_slice($playlists, self::$offset, self::$limit);
        }

        $total_count = (AmpConfig::get('hide_search', false))
            ? Catalog::get_update_info('search', $user->id) + Catalog::get_update_info('playlist', $user->id)
            : Catalog::get_update_info('playlist', $user->id);
        $string = "<total_count>" . $total_count . "</total_count>\n";

        // Foreach the playlist ids
        foreach ($playlists as $playlist_id) {
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
                $playitem_total = (int)$playlist->last_count;
            } else {
                $playlist = new Playlist($playlist_id);
                if ($playlist->isNew()) {
                    continue;
                }
                $object_type    = 'playlist';
                $playitem_total = $playlist->get_media_count('song');
            }
            if ($songs) {
                $items          = '';
                $playlisttracks = $playlist->get_items();
                foreach ($playlisttracks as $objects) {
                    if ($objects['object_type'] === LibraryItemEnum::SONG) {
                        $items .= "\t\t<playlisttrack id=\"" . $objects['object_id'] . "\">" . $objects['track'] . "</playlisttrack>\n";
                    }
                }
            } else {
                $items = $playitem_total;
            }
            $art_url       = Art::url($playlist->id, $object_type, Core::get_request('auth'));
            $playlist_name = $playlist->get_fullname();
            $playlist_user = $playlist->username;
            $playlist_type = $playlist->type;

            $rating      = new Rating($playlist->id, $object_type);
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($playlist->id, $object_type);

            // Build this element
            $string .= "<playlist id=\"" . $playlist_id . "\">\n\t<name><![CDATA[" . $playlist_name . "]]></name>\n\t<owner><![CDATA[" . $playlist_user . "]]></owner>\n\t<items>" . $items . "</items>\n\t<type>" . $playlist_type . "</type>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<has_access>" . ($playlist->has_access($user) ? 1 : 0) . "</has_access>\n\t<has_art>" . ($playlist->has_art() ? 1 : 0) . "</has_art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . (string)$rating->get_average_rating() . "</averagerating>\n</playlist>\n";
        } // end foreach

        return self::output_xml($string);
    }

    /**
     * shares
     *
     * This returns shares to the user, in a pretty xml document with the information
     *
     * @param int[] $shares Share id's to include
     * @param User $user
     */
    public static function shares($shares, $user): string
    {
        if ((count($shares) > self::$limit || self::$offset > 0) && self::$limit) {
            $shares = array_splice($shares, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_update_info('share', $user->id) . "</total_count>\n";

        foreach ($shares as $share_id) {
            $share = new Share($share_id);
            $string .= "<share id=\"$share_id\">\n\t<name><![CDATA[" . $share->getObjectName() . "]]></name>\n\t<user><![CDATA[" . $share->getUserName() . "]]></user>\n\t<allow_stream>" . $share->allow_stream . "</allow_stream>\n\t<allow_download>" . $share->allow_download . "</allow_download>\n\t<creation_date>" . $share->creation_date . "</creation_date>\n\t<lastvisit_date>" . $share->lastvisit_date . "</lastvisit_date>\n\t<object_type><![CDATA[" . $share->object_type . "]]></object_type>\n\t<object_id>" . $share->object_id . "</object_id>\n\t<expire_days>" . $share->expire_days . "</expire_days>\n\t<max_counter>" . $share->max_counter . "</max_counter>\n\t<counter>" . $share->counter . "</counter>\n\t<secret><![CDATA[" . $share->secret . "]]></secret>\n\t<public_url><![CDATA[" . $share->public_url . "]]></public_url>\n\t<description><![CDATA[" . $share->description . "]]></description>\n</share>\n";
        } // end foreach

        return self::output_xml($string);
    }

    /**
     * bookmarks
     *
     * This returns bookmarks to the user, in a pretty xml document with the information
     *
     * @param list<int> $bookmarks Bookmark id's to include
     * @param bool $include if true include the object in the bookmark
     */
    public static function bookmarks(array $bookmarks, bool $include = false): string
    {
        $bookmarkRepository = self::getBookmarkRepository();

        $string = "";
        foreach ($bookmarks as $bookmark_id) {
            $bookmark = $bookmarkRepository->findById($bookmark_id);
            if ($bookmark === null) {
                continue;
            }

            $string .= "<bookmark id=\"$bookmark_id\">\n\t<user><![CDATA[" . $bookmark->getUserName() . "]]></user>\n\t<object_type><![CDATA[" . $bookmark->object_type . "]]></object_type>\n\t<object_id>" . $bookmark->object_id . "</object_id>\n\t<position>" . $bookmark->position . "</position>\n\t<client><![CDATA[" . $bookmark->comment . "]]></client>\n\t<creation_date>" . $bookmark->creation_date . "</creation_date>\n\t<update_date><![CDATA[" . $bookmark->update_date . "]]></update_date>\n";
            if ($include) {
                $user = User::get_from_username($bookmark->getUserName());
                switch ($bookmark->object_type) {
                    case 'song':
                        $string .= self::songs([$bookmark->object_id], $user, false);
                        break;
                    case 'podcast_episode':
                        $string .= self::podcast_episodes([$bookmark->object_id], $user, false);
                        break;
                    case 'video':
                        $string .= self::videos([$bookmark->object_id], $user, false);
                        break;
                }
            }
            $string .= "</bookmark>\n";
        } // end foreach

        return self::output_xml($string);
    }

    /**
     * catalogs
     *
     * This returns catalogs to the user, in a pretty xml document with the information
     *
     * @param int[] $catalogs group of catalog id's
     * @param User $user
     */
    public static function catalogs($catalogs, $user): string
    {
        if ((count($catalogs) > self::$limit || self::$offset > 0) && self::$limit) {
            $catalogs = array_splice($catalogs, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_update_info('catalog', $user->id) . "</total_count>\n";

        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }
            $catalog->format();
            $string .= "<catalog id=\"$catalog_id\">\n\t<name><![CDATA[" . $catalog->name . "]]></name>\n\t<type><![CDATA[" . $catalog->catalog_type . "]]></type>\n\t<gather_types><![CDATA[" . $catalog->gather_types . "]]></gather_types>\n\t<enabled>" . $catalog->enabled . "</enabled>\n\t<last_add>" . $catalog->last_add . "</last_add>\n\t<last_clean>" . $catalog->last_clean . "</last_clean>\n\t<last_update>" . $catalog->last_update . "</last_update>\n\t<path><![CDATA[" . $catalog->f_info . "]]></path>\n\t<rename_pattern><![CDATA[" . $catalog->rename_pattern . "]]></rename_pattern>\n\t<sort_pattern><![CDATA[" . $catalog->sort_pattern . "]]></sort_pattern>\n</catalog>\n";
        } // end foreach

        return self::output_xml($string);
    }

    /**
     * podcasts
     *
     * This returns podcasts to the user, in a pretty xml document with the information
     *
     * @param int[] $podcasts Podcast id's to include
     * @param User $user
     * @param bool $episodes include the episodes of the podcast //optional
     */
    public static function podcasts($podcasts, $user, $episodes = false): string
    {
        if ((count($podcasts) > self::$limit || self::$offset > 0) && self::$limit) {
            $podcasts = array_splice($podcasts, self::$offset, self::$limit);
        }

        $podcastRepository = self::getPodcastRepository();

        $string = "<total_count>" . Catalog::get_update_info('podcast', $user->id) . "</total_count>\n";

        foreach ($podcasts as $podcast_id) {
            $podcast = $podcastRepository->findById($podcast_id);
            if ($podcast === null) {
                continue;
            }

            $rating      = new Rating($podcast_id, 'podcast');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($podcast_id, 'podcast');
            $art_url     = Art::url($podcast_id, 'podcast', Core::get_request('auth'));
            $string .= "<podcast id=\"$podcast_id\">\n\t<name><![CDATA[" . $podcast->get_fullname() . "]]></name>\n\t<description><![CDATA[" . $podcast->get_description() . "]]></description>\n\t<language><![CDATA[" . scrub_out($podcast->getLanguage()) . "]]></language>\n\t<copyright><![CDATA[" . scrub_out($podcast->getCopyright()) . "]]></copyright>\n\t<feed_url><![CDATA[" . $podcast->getFeedUrl() . "]]></feed_url>\n\t<generator><![CDATA[" . scrub_out($podcast->getGenerator()) . "]]></generator>\n\t<website><![CDATA[" . scrub_out($podcast->getWebsite()) . "]]></website>\n\t<build_date><![CDATA[" . $podcast->getLastBuildDate()->format(DATE_ATOM) . "]]></build_date>\n\t<sync_date><![CDATA[" . $podcast->getLastSyncDate()->format(DATE_ATOM) . "]]></sync_date>\n\t<public_url><![CDATA[" . $podcast->get_link() . "]]></public_url>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<has_art>" . ($podcast->has_art() ? 1 : 0) . "</has_art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . (string)$rating->get_average_rating() . "</averagerating>\n";
            if ($episodes) {
                $results = $podcast->getEpisodeIds();
                if (!empty($results)) {
                    $string .= self::podcast_episodes($results, $user, false);
                }
            }
            $string .= "\t</podcast>\n";
        } // end foreach

        return self::output_xml($string);
    }

    /**
     * podcast_episodes
     *
     * This returns podcasts to the user, in a pretty xml document with the information
     *
     * @param int[] $podcast_episodes Podcast_Episode id's to include
     * @param User $user
     * @param bool $full_xml whether to return a full XML document or just the node.
     */
    public static function podcast_episodes($podcast_episodes, $user, $full_xml = true): string
    {
        if ((count($podcast_episodes) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $podcast_episodes = array_splice($podcast_episodes, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('podcast_episode', $user->id) . "</total_count>\n" : '';

        foreach ($podcast_episodes as $episode_id) {
            $episode = new Podcast_Episode($episode_id);
            if ($episode->isNew()) {
                continue;
            }
            $episode->format();
            $rating      = new Rating($episode_id, 'podcast_episode');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($episode_id, 'podcast_episode');
            $art_url     = Art::url($episode->podcast, 'podcast', Core::get_request('auth'));
            $string .= "\t<podcast_episode id=\"$episode_id\">\n\t\t<title><![CDATA[" . $episode->get_fullname() . "]]></title>\n\t\t<name><![CDATA[" . $episode->get_fullname() . "]]></name>\n\t\t<podcast id=\"$episode->podcast\">\n\t\t\t<name><![CDATA[" . $episode->getPodcastName() . "]]></name></podcast>\n\t\t<description><![CDATA[" . $episode->get_description() . "]]></description>\n\t\t<category><![CDATA[" . $episode->getCategory() . "]]></category>\n\t\t<author><![CDATA[" . $episode->getAuthor() . "]]></author>\n\t\t<author_full><![CDATA[" . $episode->getAuthor() . "]]></author_full>\n\t\t<website><![CDATA[" . $episode->getWebsite() . "]]></website>\n\t\t<pubdate><![CDATA[" . $episode->getPubDate()->format(DATE_ATOM) . "]]></pubdate>\n\t\t<state><![CDATA[" . $episode->getState()->toDescription() . "]]></state>\n\t\t<filelength><![CDATA[" . $episode->f_time_h . "]]></filelength>\n\t\t<filesize><![CDATA[" . $episode->getSizeFormatted() . "]]></filesize>\n\t\t<filename><![CDATA[" . $episode->getFileName() . "]]></filename>\n\t\t<mime><![CDATA[" . $episode->mime . "]]></mime>\n\t\t<time>" . (int)$episode->time . "</time>\n\t\t<size>" . (int)$episode->size . "</size>\n\t<bitrate>" . $episode->bitrate . "</bitrate>\n\t<stream_bitrate>" . $episode->bitrate . "</stream_bitrate>\n\t<rate>" . $episode->rate . "</rate>\n\t<mode><![CDATA[" . $episode->mode . "]]></mode>\n\t<channels>" . $episode->channels . "</channels>\n\t\t<public_url><![CDATA[" . $episode->get_link() . "]]></public_url>\n\t\t<url><![CDATA[" . $episode->play_url('', 'api', false, $user->getId(), $user->streamtoken) . "]]></url>\n\t\t<catalog>" . $episode->catalog . "</catalog>\n\t\t<art><![CDATA[" . $art_url . "]]></art>\n\t<has_art>" . ($episode->has_art() ? 1 : 0) . "</has_art>\n\t\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t\t\t<rating>" . $user_rating . "</rating>\n\t\t<averagerating>" . (string)$rating->get_average_rating() . "</averagerating>\n\t\t<playcount>" . $episode->total_count . "</playcount>\n\t\t<played>" . $episode->played . "</played>\n\t</podcast_episode>\n";
        } // end foreach

        return self::output_xml($string, $full_xml);
    }

    /**
     * songs
     *
     * This returns an xml document from an array of song ids.
     * (Spiffy isn't it!)
     * @param int[] $songs
     * @param User $user
     * @param bool $full_xml
     */
    public static function songs($songs, $user, $full_xml = true): string
    {
        if ((count($songs) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $songs = array_slice($songs, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('song', $user->id) . "</total_count>\n" : '';

        Song::build_cache($songs);
        Stream::set_session(Core::get_request('auth'));

        $playlist_track = 0;

        // Foreach the ids!
        foreach ($songs as $song_id) {
            $song = new Song($song_id);

            // If the song id is invalid/null
            if ($song->isNew()) {
                continue;
            }

            $song->format();
            $song_album   = self::getAlbumRepository()->getNames($song->album);
            $song_artist  = Artist::get_name_array_by_id($song->artist);
            $song_artists = [];
            foreach ($song->get_artists() as $artist_id) {
                $song_artists[] = Artist::get_name_array_by_id($artist_id);
            }
            $tag_string    = self::genre_string(Tag::get_top_tags('song', $song_id));
            $rating        = new Rating($song_id, 'song');
            $user_rating   = $rating->get_user_rating($user->getId());
            $flag          = new Userflag($song_id, 'song');
            $show_song_art = AmpConfig::get('show_song_art', false);
            $has_art       = Art::has_db($song->id, 'song');
            $art_object    = ($show_song_art && $has_art) ? $song->id : $song->album;
            $art_type      = ($show_song_art && $has_art) ? 'song' : 'album';
            $art_url       = Art::url($art_object, $art_type, Core::get_request('auth'));
            $songType      = $song->type;
            $songMime      = $song->mime;
            $songBitrate   = $song->bitrate;
            $play_url      = $song->play_url('', AccessTypeEnum::API->value, false, $user->id, $user->streamtoken);
            $license       = $song->getLicense();
            if ($license !== null) {
                $licenseLink = $license->getLinkFormatted();
            } else {
                $licenseLink = '';
            }

            $playlist_track++;

            $string .= "<song id=\"" . $song->id . "\">\n\t<name><![CDATA[" . $song->f_name . "]]></name>\n\t<title><![CDATA[" . $song->get_fullname() . "]]></title>\n";
            foreach ($song_artists as $this_artist) {
                $string .= "\t<artist id=\"" . $this_artist['id'] . "\"><name><![CDATA[" . $this_artist['name'] . "]]></name>\n\t<prefix><![CDATA[" . $this_artist['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $this_artist['basename'] . "]]></basename>\n</artist>\n";
            }
            $string .= "\t<album id=\"" . $song->album . "\"><name><![CDATA[" . $song_album['name'] . "]]></name>\n\t<prefix><![CDATA[" . $song_album['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $song_album['basename'] . "]]></basename>\n</album>\n";
            if ($song->get_album_artist_fullname() != "") {
                $album_artist = ($song->artist !== $song->albumartist)
                    ? Artist::get_name_array_by_id($song->albumartist)
                    : $song_artist;
                $string .= "\t<albumartist id=\"" . $song->albumartist . "\"><name><![CDATA[" . $album_artist['name'] . "]]></name>\n\t<prefix><![CDATA[" . $album_artist['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $album_artist['basename'] . "]]></basename>\n</albumartist>\n";
            }
            $string .= "\t<disk><![CDATA[" . $song->disk . "]]></disk>\n\t<disksubtitle><![CDATA[" . $song->disksubtitle . "]]></disksubtitle>\n\t<track>" . $song->track . "</track>\n" . $tag_string . "\t<filename><![CDATA[" . $song->file . "]]></filename>\n\t<playlisttrack>" . $playlist_track . "</playlisttrack>\n\t<time>" . $song->time . "</time>\n\t<year>" . $song->year . "</year>\n\t<format>" . $songType . "</format>\n\t<stream_format>" . $song->type . "</stream_format>\n\t<bitrate>" . $songBitrate . "</bitrate>\n\t<stream_bitrate>" . $song->bitrate . "</stream_bitrate>\n\t<rate>" . $song->rate . "</rate>\n\t<mode><![CDATA[" . $song->mode . "]]></mode>\n\t<mime><![CDATA[" . $songMime . "]]></mime>\n\t<stream_mime><![CDATA[" . $song->mime . "]]></stream_mime>\n\t<url><![CDATA[" . $play_url . "]]></url>\n\t<size>" . $song->size . "</size>\n\t<mbid><![CDATA[" . $song->mbid . "]]></mbid>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<has_art>" . ($song->has_art() ? 1 : 0) . "</has_art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . (string)$rating->get_average_rating() . "</averagerating>\n\t<playcount>" . $song->total_count . "</playcount>\n\t<catalog>" . $song->getCatalogId() . "</catalog>\n\t<composer><![CDATA[" . $song->composer . "]]></composer>\n\t<channels>" . $song->channels . "</channels>\n\t<comment><![CDATA[" . $song->comment . "]]></comment>\n\t<license><![CDATA[" . $licenseLink . "]]></license>\n\t<publisher><![CDATA[" . $song->label . "]]></publisher>\n\t<language>" . $song->language . "</language>\n\t<lyrics><![CDATA[" . $song->lyrics . "]]></lyrics>\n\t<replaygain_album_gain>" . $song->replaygain_album_gain . "</replaygain_album_gain>\n\t<replaygain_album_peak>" . $song->replaygain_album_peak . "</replaygain_album_peak>\n\t<replaygain_track_gain>" . $song->replaygain_track_gain . "</replaygain_track_gain>\n\t<replaygain_track_peak>" . $song->replaygain_track_peak . "</replaygain_track_peak>\n\t<r128_album_gain>" . $song->r128_album_gain . "</r128_album_gain>\n\t<r128_track_gain>" . $song->r128_track_gain . "</r128_track_gain>\n";

            /** @var Metadata $metadata */
            foreach ($song->getMetadata() as $metadata) {
                $field = $metadata->getField();

                if ($field !== null) {
                    $meta_name = str_replace([' ', '(', ')', '/', '\\', '#'], '_', $field->getName());
                    $string .= "\t<" . $meta_name . "><![CDATA[" . $metadata->getData() . "]]></" . $meta_name . ">\n";
                }
            }

            $string .= "</song>\n";
        } // end foreach

        return self::output_xml($string, $full_xml);
    }

    /**
     * videos
     *
     * This builds the xml document for displaying video objects
     *
     * @param int[] $videos Video id's to include
     * @param User $user
     * @param bool $full_xml
     */
    public static function videos($videos, $user, $full_xml = true): string
    {
        if ((count($videos) > self::$limit || self::$offset > 0) && self::$limit) {
            $videos = array_slice($videos, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_update_info('song', $user->id) . "</total_count>\n" : '';

        foreach ($videos as $video_id) {
            $video = new Video($video_id);
            if ($video->isNew()) {
                continue;
            }
            $video->format();
            $rating      = new Rating($video_id, 'video');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($video_id, 'video');
            $art_url     = Art::url($video_id, 'video', Core::get_request('auth'));

            $string .= "<video id=\"" . $video->id . "\">\n\t<name><![CDATA[" . $video->title . "]]></name>\n\t<title><![CDATA[" . $video->title . "]]></title>\n\t<mime><![CDATA[" . $video->mime . "]]></mime>\n\t<resolution><![CDATA[" . $video->f_resolution . "]]></resolution>\n\t<size>" . $video->size . "</size>\n" . self::genre_string($video->tags) . "\t<time><![CDATA[" . $video->time . "]]></time>\n\t<url><![CDATA[" . $video->play_url('', 'api', false, $user->getId(), $user->streamtoken) . "]]></url>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<has_art>" . ($video->has_art() ? 1 : 0) . "</has_art>\n\t<flag>" . (!$flag->get_flag($user->getId()) ? 0 : 1) . "</flag>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . (string)$rating->get_average_rating() . "</averagerating>\n\t<playcount>" . $video->total_count . "</playcount>\n</video>\n";
        } // end foreach

        return self::output_xml($string, $full_xml);
    }

    /**
     * democratic
     *
     * This handles creating an xml document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param array $object_ids Object IDs
     * @param User $user
     */
    public static function democratic($object_ids, $user): string
    {
        $democratic = Democratic::get_current_playlist($user);
        $string     = '';

        foreach ($object_ids as $row_id => $data) {
            $className = ObjectTypeToClassNameMapper::map($data['object_type']);
            /** @var Song $song */
            $song = new $className($data['object_id']);
            if ($song->isNew()) {
                continue;
            }
            $song->format();

            // FIXME: This is duplicate code and so wrong, functions need to be improved
            $tag         = new Tag($song->tags['0']);
            $song_album  = self::getAlbumRepository()->getNames($song->album);
            $song_artist = Artist::get_name_array_by_id($song->artist);
            $tag_string  = self::genre_string($song->tags);
            $rating      = new Rating($song->id, 'song');
            $user_rating = $rating->get_user_rating($user->getId());
            $art_url     = Art::url($song->album, 'album', Core::get_request('auth'));
            $songType    = $song->type;
            $songMime    = $song->mime;
            $songBitrate = $song->bitrate;
            $play_url    = $song->play_url('', AccessTypeEnum::API->value, false, $user->id, $user->streamtoken);

            $string .= "<song id=\"" . $song->id . "\">\n\t<name><![CDATA[" . $song->f_name . "]]></name>\n\t<title><![CDATA[" . $song->get_fullname() . "]]></title>\n" .
                "\t<artist id=\"" . $song->artist . "\"><name><![CDATA[" . $song_artist['name'] . "]]></name>\n\t<prefix><![CDATA[" . $song_artist['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $song_artist['basename'] . "]]></basename>\n</artist>\n" .
                "\t<album id=\"" . $song->album . "\"><name><![CDATA[" . $song_album['name'] . "]]></name>\n\t<prefix><![CDATA[" . $song_album['prefix'] . "]]></prefix>\n\t<basename><![CDATA[" . $song_album['basename'] . "]]></basename>\n</album>\n" .
                "\t<genre id=\"" . $tag->id . "\"><name><![CDATA[" . $tag->name . "]]></name></genre>\n";
            $string .= $tag_string . "\t<track>" . $song->track . "</track>\n\t<time><![CDATA[" . $song->time . "]]></time>\n\t<format>" . $songType . "</format>\n\t<bitrate>" . $songBitrate . "</bitrate>\n\t<mime><![CDATA[" . $songMime . "]]></mime>\n\t<url><![CDATA[" . $play_url . "]]></url>\n\t<size>" . $song->size . "</size>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<has_art>" . ($song->has_art() ? 1 : 0) . "</has_art>\n\t<rating>" . $user_rating . "</rating>\n\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n<playcount>" . $song->total_count . "</playcount>\n\t<vote>" . $democratic->get_vote($row_id) . "</vote>\n</song>\n";
        } // end foreach

        return self::output_xml($string);
    }

    /**
     * user
     *
     * This handles creating an xml document for a user
     */
    public static function user(User $user, bool $fullinfo): string
    {
        $user->format();
        $art_url = Art::url($user->id, 'user', $_REQUEST['auth'] ?? '');
        $string  = "<user id=\"" . (string)$user->id . "\">\n\t<username><![CDATA[" . $user->username . "]]></username>\n";
        if ($fullinfo) {
            $string .= "\t<auth><![CDATA[" . $user->apikey . "]]></auth>\n\t<email><![CDATA[" . $user->email . "]]></email>\n\t<access>" . (int)$user->access . "</access>\n\t<streamtoken>" . $user->streamtoken . "</streamtoken>\n\t<fullname_public>" . (int)$user->fullname_public . "</fullname_public>\n\t<validation><![CDATA[" . $user->validation . "]]></validation>\n\t<disabled>" . (int)$user->disabled . "</disabled>\n";
        }
        $string .= "\t<create_date>" . (int)$user->create_date . "</create_date>\n\t<last_seen>" . (int)$user->last_seen . "</last_seen>\n\t<link><![CDATA[" . $user->get_link() . "]]></link>\n\t<website><![CDATA[" . $user->website . "]]></website>\n\t<state><![CDATA[" . $user->state . "]]></state>\n\t<city><![CDATA[" . $user->city . "]]></city>\n\t<art><![CDATA[" . $art_url . "]]></art>\n\t<has_art><![CDATA[" . $user->has_art() . "]]></has_art>\n";
        if ($user->fullname_public || $fullinfo) {
            $string .= "\t<fullname><![CDATA[" . $user->fullname . "]]></fullname>\n";
        }
        $string .= "</user>\n";

        return self::output_xml($string);
    }

    /**
     * users
     *
     * This handles creating an xml document for a user list
     *
     * @param int[] $objects User identifier list
     */
    public static function users($objects): string
    {
        if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $string = "";
        foreach ($objects as $user_id) {
            $user = new User($user_id);
            $string .= "<user id=\"" . (string)$user->id . "\">\n\t<username><![CDATA[" . $user->username . "]]></username>\n</user>\n";
        }

        return self::output_xml($string);
    }

    /**
     * now_playing
     *
     * This handles creating a xml document for a now_playing list
     *
     * @param list<array{
     *   media: library_item,
     *   client: User,
     *   agent: string,
     *   expire: int
     *  }> $results
     */
    public static function now_playing(array $results): string
    {
        $string = "";

        foreach ($results as $now_playing) {
            $user = $now_playing['client'];
            if ($user->isNew()) {
                continue;
            }
            $media = $now_playing['media'];
            $string .= "\t<now_playing id=\"" . $media->getId() . "\">\n" . "\t\t<type><![CDATA[" . $media->getMediaType()->value . "]]></type>\n" . "\t\t<client><![CDATA[" . $now_playing['agent'] . "]]></client>\n" . "\t\t<expire>" . (int) $now_playing['expire'] . "</expire>\n" . "\t\t<user id=\"" . $user->getId() . "\">\n\t\t\t<username><![CDATA[" . $user->getUsername() . "]]></username>\n\t\t</user>\n" . "\t</now_playing>\n";
        }

        return self::output_xml($string);
    }

    /**
     * shouts
     *
     * This handles creating a xml document for a shout list
     *
     * @param list<Shoutbox> $shouts Shout identifier list
     */
    public static function shouts(array $shouts): string
    {
        $string = "";

        foreach ($shouts as $shout) {
            $user = $shout->getUser();
            $string .= "\t<shout id=\"" . $shout->getId() . "\">\n\t\t<date>" . $shout->getDate()->getTimestamp() . "</date>\n\t\t<text><![CDATA[" . $shout->getText() . "]]></text>\n";
            if ($user !== null) {
                $string .= "\t\t<user id=\"" . $user->getId() . "\">\n\t\t\t<username><![CDATA[" . $user->getUsername() . "]]></username>\n\t\t</user>\n";
            }
            $string .= "\t</shout>\n";
        }

        return self::output_xml($string);
    }

    /**
     * @param string $string
     * @param bool $full_xml
     */
    public static function output_xml($string, $full_xml = true): string
    {
        $xml = "";
        if ($full_xml) {
            $xml .= self::_header();
        }
        $xml .= Ui::clean_utf8($string);
        if ($full_xml) {
            $xml .= self::_footer();
        }
        // return formatted xml when asking for full_xml
        if ($full_xml) {
            $dom = new DOMDocument();
            // format the string
            $dom->preserveWhiteSpace = false;
            if (!$dom->loadXML($xml)) {
                return $xml;
            }
            $dom->formatOutput = true;

            return $dom->saveXML() ?: '';
        }

        return $xml;
    }

    /**
     * timeline
     *
     * This handles creating an xml document for an activity list
     *
     * @param int[] $activities Activity identifier list
     */
    public static function timeline($activities): string
    {
        $string = "";
        foreach ($activities as $activity_id) {
            $activity = new Useractivity($activity_id);
            $user     = new User($activity->user);
            $string .= "\t<activity id=\"" . $activity_id . "\">\n\t\t<date>" . $activity->activity_date . "</date>\n\t\t<object_type><![CDATA[" . $activity->object_type . "]]></object_type>\n\t\t<object_id>" . $activity->object_id . "</object_id>\n\t\t<action><![CDATA[" . $activity->action . "]]></action>\n";
            if ($user->isNew() === false) {
                $string .= "\t\t<user id=\"" . (string)$user->id . "\">\n\t\t\t<username><![CDATA[" . $user->username . "]]></username>\n\t\t</user>\n";
            }
            $string .= "\t</activity>\n";
        }

        return self::_header() . $string . self::_footer();
    }

    /**
     * deleted
     *
     * This takes an array of deleted objects and return XML based on the type of object
     * we want
     *
     * @param string $object_type ('song', 'podcast_episode', 'video')
     * @param array $objects deleted object list
     */
    public static function deleted($object_type, $objects): string
    {
        if ((count($objects) > self::$limit || self::$offset > 0) && self::$limit) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }

        $string = '';
        // here is where we call the object type
        foreach ($objects as $row) {
            switch ($object_type) {
                case 'song':
                    // id, addition_time, delete_time, title, file, `catalog`, total_count, total_skip, update_time, album, artist
                    $string .= "<deleted_song id=\"" . $row['id'] . "\">\n\t<addition_time>" . $row['addition_time'] . "</addition_time>\n\t<delete_time>" . $row['delete_time'] . "</delete_time>\n\t<title><![CDATA[" . $row['title'] . "]]></title>\n\t<file><![CDATA[" . $row['file'] . "]]></file>\n\t<catalog>" . $row['catalog'] . "</catalog>\n\t<total_count>" . $row['total_count'] . "</total_count>\n\t<total_skip>" . $row['total_skip'] . "</total_skip>\n\t<update_time>" . $row['update_time'] . "</update_time>\n\t<album>" . $row['album'] . "</album>\n\t<artist>" . $row['artist'] . "</artist>\n</deleted_song>\n";
                    break;
                case 'podcast_episode':
                    // id, addition_time, delete_time, title, file, `catalog`, total_count, total_skip, podcast
                    $string .= "\t<deleted_podcast_episode id=\"" . $row['id'] . "\">\n\t<addition_time>" . $row['addition_time'] . "</addition_time>\n\t<delete_time>" . $row['delete_time'] . "</delete_time>\n\t<title><![CDATA[" . $row['title'] . "]]></title>\n\t<file><![CDATA[" . $row['file'] . "]]></file>\n\t<catalog>" . $row['catalog'] . "</catalog>\n\t<total_count>" . $row['total_count'] . "</total_count>\n\t<total_skip>" . $row['total_skip'] . "</total_skip>\n\t<played>" . $row['podcast'] . "</played>\n\t</deleted_podcast_episode>\n";
                    break;
                case 'video':
                    // id, addition_time, delete_time, title, file, catalog, total_count, total_skip
                    $string .= "<deleted_video id=\"" . $row['id'] . "\">\n\t<addition_time>" . $row['addition_time'] . "</addition_time>\n\t<delete_time>" . $row['delete_time'] . "</delete_time>\n\t<title><![CDATA[" . $row['title'] . "]]></title>\n\t<file><![CDATA[" . $row['file'] . "]]></file>\n\t<catalog>" . $row['catalog'] . "</catalog>\n\t<total_count>" . $row['total_count'] . "</total_count>\n\t<total_skip>" . $row['total_skip'] . "</total_skip>\n</deleted_video>\n";
            }
        } // end foreach objects

        return self::output_xml($string);
    }

    /**
     * _header
     *
     * this returns a standard header, there are a few types
     * so we allow them to pass a type if they want to
     */
    private static function _header(): string
    {
        return "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset') . "\" ?>\n<root>\n";
    }

    /**
     * _footer
     *
     * this returns the footer for this document, these are pretty boring
     */
    private static function _footer(): string
    {
        return "\n</root>\n";
    }

    /**
     * @deprecated
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated
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
