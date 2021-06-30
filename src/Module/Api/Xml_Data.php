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

declare(strict_types=0);

namespace Ampache\Module\Api;

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\License;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Video;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use DOMDocument;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use SimpleXMLElement;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Useractivity;
use Ampache\Repository\Model\Userflag;

/**
 * XML_Data Class
 *
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls
 */
class Xml_Data
{
    // This is added so that we don't pop any webservers
    private static $limit  = 5000;
    private static $offset = 0;
    private static $type   = '';

    /**
     * set_offset
     *
     * This takes an int and changes the offset
     *
     * @param integer $offset Change the starting position of your results. (e.g 5001 when selecting in groups of 5000)
     */
    public static function set_offset($offset)
    {
        self::$offset = (int)$offset;
    } // set_offset

    /**
     * set_limit
     *
     * This sets the limit for any ampache transactions
     *
     * @param  integer $limit Set a limit on your results
     * @return boolean
     */
    public static function set_limit($limit)
    {
        if (!$limit) {
            return false;
        }

        self::$limit = (strtolower((string) $limit) == "none") ? null : (int) $limit;

        return true;
    } // set_limit

    /**
     * set_type
     *
     * This sets the type of XML_Data we are working on
     *
     * @param  string  $type XML_Data type
     * @return boolean
     */
    public static function set_type($type)
    {
        if (!in_array($type, array('rss', 'xspf', 'itunes'))) {
            return false;
        }

        self::$type = $type;

        return true;
    } // set_type

    /**
     * error
     *
     * This generates a standard XML Error message
     * nothing fancy here...
     *
     * @param  string $code Error code
     * @param  string $string Error message
     * @param  string $action
     * @param  string $type
     * @return string return error message xml
     */
    public static function error($code, $string, $action, $type)
    {
        $xml_string = "\t<error errorCode=\"$code\">" .
            "\n\t\t<errorAction><![CDATA[$action]]></errorAction>" .
            "\n\t\t<errorType><![CDATA[$type]]></errorType>" .
            "\n\t\t<errorMessage><![CDATA[$string]]></errorMessage>" .
            "\n\t</error>";

        return self::output_xml($xml_string);
    } // error

    /**
     * success
     *
     * This generates a standard XML Success message
     * nothing fancy here...
     *
     * @param  string $string success message
     * @param  array  $return_data
     * @return string return success message xml
     */
    public static function success($string, $return_data = array())
    {
        $xml_string = "\t<success code=\"1\"><![CDATA[$string]]></success>";
        foreach ($return_data as $title => $data) {
            $xml_string .= "\n\t<$title><![CDATA[$data]]></$title>";
        }

        return self::output_xml($xml_string);
    } // success

    /**
     * empty
     *
     * This generates an empty root element
     */
    public static function empty()
    {
        return "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset') . "\" ?>\n<root>\n</root>\n";
    } // empty

    /**
     * header
     *
     * This returns the header
     *
     * @param  string $title
     * @return string return xml
     * @see _header()
     */
    public static function header($title = null)
    {
        return self::_header($title);
    } // header

    /**
     * footer
     *
     * This returns the footer
     *
     * @return string return xml
     * @see _footer()
     */
    public static function footer()
    {
        return self::_footer();
    } // footer

    /**
     * genre_string
     *
     * This returns the formatted 'genre' string for an xml document
     * @param  array  $tags
     * @return string
     */
    private static function genre_string($tags)
    {
        $string = '';

        if (!empty($tags)) {
            $atags = array();
            foreach ($tags as $tag_id => $data) {
                if (array_key_exists($data['id'], $atags)) {
                    $atags[$data['id']]['count']++;
                } else {
                    $atags[$data['id']] = array(
                        'name' => $data['name'],
                        'count' => 1
                    );
                }
            }

            foreach ($atags as $tag => $data) {
                $string .= "\t<genre id=\"" . $tag . "\"><![CDATA[" . $data['name'] . "]]></genre>\n";
            }
        }

        return $string;
    } // genre_string

    /**
     * output_xml_from_array
     * This takes a one dimensional array and creates a XML document from it. For
     * use primarily by the ajax mojo.
     * @param  array   $array
     * @param  boolean $callback
     * @param  string  $type
     * @return string
     */
    public static function output_xml_from_array($array, $callback = false, $type = '')
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
                    } else {
                        if ($key == "key") {
                            $string .= "\t\t<$key>$value</$key>\n";
                        } elseif (is_int($value)) {
                            $string .= "\t\t\t<key>$key</key><integer>$value</integer>\n";
                        } elseif ($key == "Date Added") {
                            $string .= "\t\t\t<key>$key</key><date>$value</date>\n";
                        } elseif (is_string($value)) {
                            /* We need to escape the value */
                            $string .= "\t\t\t<key>$key</key><string><![CDATA[$value]]></string>\n";
                        }
                    }
                } // end foreach

                return $string;
            case 'xspf':
                foreach ($array as $key => $value) {
                    if (is_array($value)) {
                        $value = xoutput_from_array($value, true, $type);
                        $string .= "\t\t<$key>\n$value\t\t</$key>\n";
                    } else {
                        if ($key == "key") {
                            $string .= "\t\t<$key>$value</$key>\n";
                        } elseif (is_numeric($value)) {
                            $string .= "\t\t\t<$key>$value</$key>\n";
                        } elseif (is_string($value)) {
                            /* We need to escape the value */
                            $string .= "\t\t\t<$key><![CDATA[$value]]></$key>\n";
                        }
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
                        $string .= "\t<content div=\"$key\"><![CDATA[$value]]></content>\n";
                    }
                    // end foreach elements
                }
                if (!$callback) {
                    $string = '<?xml version="1.0" encoding="utf-8" ?>' . "\n<root>\n" . $string . "</root>\n";
                }

                return Ui::clean_utf8($string);
        }
    } // output_from_array

    /**
     * keyed_array
     *
     * This will build an xml document from a key'd array,
     *
     * @param  array          $array keyed array of objects (key => value, key => value)
     * @param  boolean        $callback (don't output xml when true)
     * @param  string|boolean $object
     * @return string         return xml
     */
    public static function keyed_array($array, $callback = false, $object = false)
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
                $string .= ($object) ? "\t<$object index=\"" . $key . "\"><![CDATA[$value]]></$object>\n" : "\t<$key$attribute><![CDATA[$value]]></$key>\n";
            }
        } // end foreach

        if (!$callback) {
            $string = self::output_xml($string);
        }

        return $string;
    } // keyed_array

    /**
     * object_array
     *
     * This will build an xml document from an array of arrays, an id is required for the array data
     * <root>
     *   <$object_type> //optional
     *     <$item id="123">
     *       <data></data>
     *
     * @param  array  $array
     * @param  string $item
     * @param  string $object_type
     * @return string return xml
     */
    public static function object_array($array, $item, $object_type = '')
    {
        $string = ($object_type == '') ? '' : "<$object_type>\n";
        // Foreach it
        foreach ($array as $object) {
            $string .= "\t<$item id=\"" . $object['id'] . "\">\n";
            foreach ($object as $name => $value) {
                $filter = (is_numeric($value)) ? $value : "<![CDATA[$value]]>";
                $string .= ($name !== 'id') ? "\t\t<$name>$filter</$name>\n" : '';
            }
            $string .= "\t</$item>\n";
        } // end foreach
        $string .= ($object_type == '') ? '' : "</$object_type>";

        return self::output_xml($string);
    } // object_array

    /**
     * indexes
     *
     * This takes an array of object_ids and return XML based on the type of object
     * we want
     *
     * @param  array   $objects Array of object_ids (Mixed string|int)
     * @param  string  $object_type 'artist'|'album'|'song'|'playlist'|'share'|'podcast'|'podcast_episode'|'video'|'live_stream'
     * @param  integer $user_id
     * @param  boolean $full_xml whether to return a full XML document or just the node.
     * @param  boolean $include include episodes from podcasts or tracks in a playlist
     * @return string  return xml
     */
    public static function indexes($objects, $object_type, $user_id = null, $full_xml = true, $include = false)
    {
        if ((count($objects) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $objects = array_splice($objects, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_count($object_type) . "</total_count>\n" : '';

        // here is where we call the object type
        foreach ($objects as $object_id) {
            switch ($object_type) {
                case 'artist':
                    if ($include) {
                        $string .= self::artists(array($object_id), array('songs', 'albums'), $user_id, false);
                    } else {
                        $artist = new Artist($object_id);
                        $artist->format();
                        $albums = static::getAlbumRepository()->getByArtist($object_id, null, true);
                        $string .= "<$object_type id=\"" . $object_id . "\">\n" .
                            "\t<name><![CDATA[" . $artist->f_name . "]]></name>\n";
                        foreach ($albums as $album_id) {
                            if ($album_id > 0) {
                                $album = new Album($album_id);
                                $string .= "\t<album id=\"" . $album_id .
                                    '"><![CDATA[' . $album->f_name .
                                    "]]></album>\n";
                            }
                        }
                        $string .= "</$object_type>\n";
                    }
                    break;
                case 'album':
                    if ($include) {
                        $string .= self::albums(array($object_id), array('songs'), $user_id, false);
                    } else {
                        $album = new Album($object_id);
                        $album->format();
                        $string .= "<$object_type id=\"" . $object_id . "\">\n" .
                            "\t<name><![CDATA[" . $album->f_name . "]]></name>\n" .
                            "\t\t<artist id=\"" . $album->album_artist . "\"><![CDATA[" . $album->f_album_artist_name . "]]></artist>\n" .
                            "</$object_type>\n";
                    }
                    break;
                case 'song':
                    $song = new Song($object_id);
                    $song->format();
                    $string .= "<$object_type id=\"" . $object_id . "\">\n" .
                            "\t<title><![CDATA[" . $song->title . "]]></title>\n" .
                            "\t<name><![CDATA[" . $song->f_title . "]]></name>\n" .
                            "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_artist_name() . "]]></artist>\n" .
                            "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_name() . "]]></album>\n" .
                            "\t<albumartist id=\"" . $song->albumartist . "\"><![CDATA[" . $song->get_album_artist_name() . "]]></albumartist>\n" .
                            "\t<disk><![CDATA[" . $song->disk . "]]></disk>\n" .
                            "\t<track>" . $song->track . "</track>\n" .
                            "</$object_type>\n";
                    break;
                case 'playlist':
                    if ((int) $object_id === 0) {
                        $playlist = new Search((int) str_replace('smart_', '', (string) $object_id));
                        $playlist->format();

                        $playlist_name  = Search::get_name_byid(str_replace('smart_', '', (string) $object_id));
                        $playlist_user  = ($playlist->type !== 'public')
                            ? $playlist->f_user
                            : $playlist->type;
                        $last_count     = ((int) $playlist->last_count > 0) ? $playlist->last_count : 5000;
                        $playitem_total = ($playlist->limit == 0) ? $last_count : $playlist->limit;
                    } else {
                        $playlist = new Playlist($object_id);
                        $playlist->format();

                        $playlist_name  = $playlist->name;
                        $playlist_user  = $playlist->f_user;
                        $playitem_total = $playlist->get_media_count('song');
                    }
                    $songs = ($include) ? $playlist->get_items() : array();
                    $string .= "<$object_type id=\"" . $object_id . "\">\n" .
                        "\t<name><![CDATA[" . $playlist_name . "]]></name>\n" .
                        "\t<items><![CDATA[" . $playitem_total . "]]></items>\n" .
                        "\t<owner><![CDATA[" . $playlist_user . "]]></owner>\n" .
                        "\t<type><![CDATA[" . $playlist->type . "]]></type>\n";
                    $playlist_track = 0;
                    foreach ($songs as $song_id) {
                        if ($song_id['object_type'] == 'song') {
                            $playlist_track++;
                            $string .= "\t\t<playlisttrack id=\"" . $song_id['object_id'] . "\">" . $playlist_track . "</playlisttrack>\n";
                        }
                    }
                    $string .= "</$object_type>\n";
                    break;
                case 'share':
                    $string .= self::shares($objects);
                    break;
                case 'podcast':
                    $podcast = new Podcast($object_id);
                    $podcast->format();
                    $string .= "<podcast id=\"$object_id\">\n" .
                        "\t<name><![CDATA[" . $podcast->f_title . "]]></name>\n" .
                        "\t<description><![CDATA[" . $podcast->description . "]]></description>\n" .
                        "\t<language><![CDATA[" . $podcast->f_language . "]]></language>\n" .
                        "\t<copyright><![CDATA[" . $podcast->f_copyright . "]]></copyright>\n" .
                        "\t<feed_url><![CDATA[" . $podcast->feed . "]]></feed_url>\n" .
                        "\t<generator><![CDATA[" . $podcast->f_generator . "]]></generator>\n" .
                        "\t<website><![CDATA[" . $podcast->f_website . "]]></website>\n" .
                        "\t<build_date><![CDATA[" . $podcast->f_lastbuilddate . "]]></build_date>\n" .
                        "\t<sync_date><![CDATA[" . $podcast->f_lastsync . "]]></sync_date>\n" .
                        "\t<public_url><![CDATA[" . $podcast->link . "]]></public_url>\n";
                    if ($include) {
                        $episodes = $podcast->get_episodes();
                        foreach ($episodes as $episode_id) {
                            $string .= self::podcast_episodes(array($episode_id), $user_id, false);
                        }
                    }
                    $string .= "\t</podcast>\n";
                    break;
                case 'podcast_episode':
                    $string .= self::podcast_episodes($objects, $user_id);
                    break;
                case 'video':
                    $string .= self::videos($objects, $user_id);
                    break;
                case 'live_stream':
                    $live_stream = new Live_Stream($object_id);
                    $live_stream->format();
                    $string .= "<$object_type id=\"" . $object_id . "\">\n" .
                        "\t<name><![CDATA[" . $live_stream->f_name . "]]></name>\n" .
                        "\t<url><![CDATA[" . $live_stream->url . "]]></url>\n" .
                        "\t<codec><![CDATA[" . $live_stream->codec . "]]></codec>\n" .
                        "</$object_type>\n";
            }
        } // end foreach objects

        return self::output_xml($string, $full_xml);
    } // indexes

    /**
     * licenses
     *
     * This returns licenses to the user, in a pretty xml document with the information
     *
     * @param  integer[] $licenses Licence id's assigned to songs and artists
     * @return string    return xml
     */
    public static function licenses($licenses)
    {
        if ((count($licenses) > self::$limit || self::$offset > 0) && self::$limit) {
            $licenses = array_splice($licenses, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_count('license') . "</total_count>\n";

        foreach ($licenses as $license_id) {
            $license = new license($license_id);
            $string .= "<license id=\"$license_id\">\n" . "\t<name><![CDATA[$license->name]]></name>\n" . "\t<description><![CDATA[$license->description]]></description>\n" . "\t<external_link><![CDATA[$license->external_link]]></external_link>\n" . "</license>\n";
        } // end foreach

        return self::output_xml($string);
    } // licenses

    /**
     * labels
     *
     * This returns labels to the user, in a pretty xml document with the information
     *
     * @param  integer[] $labels
     * @return string    return xml
     */
    public static function labels($labels)
    {
        if ((count($labels) > self::$limit || self::$offset > 0) && self::$limit) {
            $labels = array_splice($labels, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_count('license') . "</total_count>\n";

        foreach ($labels as $label_id) {
            $label = new Label($label_id);
            $label->format();

            $string .= "<license id=\"$label_id\">\n" .
                "\t<name><![CDATA[$label->f_name]]></name>\n" .
                "\t<artists><![CDATA[$label->artists]]></artists>\n" .
                "\t<summary><![CDATA[$label->summary]]></summary>\n" .
                "\t<external_link><![CDATA[$label->link]]></external_link>\n" .
                "\t<address><![CDATA[$label->address]]></address>\n" .
                "\t<category><![CDATA[$label->category]]></category>\n" .
                "\t<email><![CDATA[$label->email]]></email>\n" .
                "\t<website><![CDATA[$label->website]]></website>\n" .
                "\t<user><![CDATA[$label->user]]></user>\n" .
                "</license>\n";
        } // end foreach

        return self::output_xml($string);
    } // labels

    /**
     * genres
     *
     * This returns genres to the user, in a pretty xml document with the information
     *
     * @param  integer[] $tags Genre id's to include
     * @return string    return xml
     */
    public static function genres($tags)
    {
        if ((count($tags) > self::$limit || self::$offset > 0) && self::$limit) {
            $tags = array_splice($tags, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_count('tag') . "</total_count>\n";

        foreach ($tags as $tag_id) {
            $tag    = new Tag($tag_id);
            $counts = $tag->count();
            $string .= "<genre id=\"$tag_id\">\n" .
                    "\t<name><![CDATA[$tag->name]]></name>\n" .
                    "\t<albums>" . (int) ($counts['album']) . "</albums>\n" .
                    "\t<artists>" . (int) ($counts['artist']) . "</artists>\n" .
                    "\t<songs>" . (int) ($counts['song']) . "</songs>\n" .
                    "\t<videos>" . (int) ($counts['video']) . "</videos>\n" .
                    "\t<playlists>" . (int) ($counts['playlist']) . "</playlists>\n" .
                    "\t<live_streams>" . (int) ($counts['live_stream']) . "</live_streams>\n" .
                    "</genre>\n";
        } // end foreach

        return self::output_xml($string);
    } // genres

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty xml document with the information
     * we want
     *
     * @param  integer[] $artists Artist id's to include
     * @param  array     $include Array of other items to include.
     * @param  integer   $user_id
     * @param  boolean   $full_xml whether to return a full XML document or just the node.
     * @return string    return xml
     */
    public static function artists($artists, $include = [], $user_id = null, $full_xml = true)
    {
        if ((count($artists) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $artists = array_splice($artists, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_count('artist') . "</total_count>\n" : '';

        Rating::build_cache('artist', $artists);

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            $artist->format();

            $rating     = new Rating($artist_id, 'artist');
            $flag       = new Userflag($artist_id, 'artist');
            $tag_string = self::genre_string($artist->tags);

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $artist_id . '&object_type=artist&auth=' . scrub_out(Core::get_request('auth'));

            // Handle includes
            $albums = (in_array("albums", $include))
                ? self::albums(static::getAlbumRepository()->getByArtist($artist_id), array(), $user_id, false)
                : '';
            $songs = (in_array("songs", $include))
                ? self::songs(static::getSongRepository()->getByArtist($artist_id), $user_id, false)
                : '';

            $string .= "<artist id=\"" . $artist->id . "\">\n" .
                    "\t<name><![CDATA[" . $artist->f_name . "]]></name>\n" .
                    $tag_string .
                    "\t<albums>" . $albums . "</albums>\n" .
                    "\t<albumcount>" . ($artist->albums ?: 0) . "</albumcount>\n" .
                    "\t<songs>" . $songs . "</songs>\n" .
                    "\t<songcount>" . ($artist->songs ?: 0) . "</songcount>\n" .
                    "\t<art><![CDATA[$art_url]]></art>\n" .
                    "\t<flag>" . (!$flag->get_flag($user_id, false) ? 0 : 1) . "</flag>\n" .
                    "\t<preciserating>" . ($rating->get_user_rating($user_id) ?: null) . "</preciserating>\n" .
                    "\t<rating>" . ($rating->get_user_rating($user_id) ?: null) . "</rating>\n" .
                    "\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                    "\t<mbid><![CDATA[" . $artist->mbid . "]]></mbid>\n" .
                    "\t<summary><![CDATA[" . $artist->summary . "]]></summary>\n" .
                    "\t<time><![CDATA[" . $artist->time . "]]></time>\n" .
                    "\t<yearformed>" . (int) $artist->yearformed . "</yearformed>\n" .
                    "\t<placeformed><![CDATA[" . $artist->placeformed . "]]></placeformed>\n" .
                    "</artist>\n";
        } // end foreach artists

        return self::output_xml($string, $full_xml);
    } // artists

    /**
     * albums
     *
     * This echos out a standard albums XML document, it pays attention to the limit
     *
     * @param  integer[] $albums Album id's to include
     * @param  array     $include Array of other items to include.
     * @param  integer   $user_id
     * @param  boolean   $full_xml whether to return a full XML document or just the node.
     * @return string    return xml
     */
    public static function albums($albums, $include = [], $user_id = null, $full_xml = true)
    {
        if ($include == null || $include == '') {
            $include = array();
        }

        if ((count($albums) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $albums = array_splice($albums, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_count('album') . "</total_count>\n" : '';
        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');

        Rating::build_cache('album', $albums);

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $album->format();

            $disk   = $album->disk;
            $rating = new Rating($album_id, 'album');
            $flag   = new Userflag($album_id, 'album');
            $year   = ($original_year && $album->original_year)
                ? $album->original_year
                : $album->year;

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $album->id . '&object_type=album&auth=' . scrub_out(Core::get_request('auth'));

            $string .= "<album id=\"" . $album->id . "\">\n" . "\t<name><![CDATA[" . $album->f_name . "]]></name>\n";

            // Do a little check for artist stuff
            if ($album->f_album_artist_name != "") {
                $string .= "\t<artist id=\"$album->album_artist\"><![CDATA[$album->f_album_artist_name]]></artist>\n";
            } elseif ($album->artist_count != 1) {
                $string .= "\t<artist id=\"0\"><![CDATA[Various]]></artist>\n";
            } else {
                $string .= "\t<artist id=\"$album->album_artist\"><![CDATA[$album->f_artist_name]]></artist>\n";
            }

            // Handle includes
            $songs = (in_array("songs", $include))
                ? self::songs(static::getSongRepository()->getByAlbum($album->id), $user_id, false)
                : '';

            // count multiple disks
            if ($album->allow_group_disks) {
                $disk = (count($album->album_suite) <= 1) ? $album->disk : count($album->album_suite);
            }

            $string .= "\t<time>" . $album->total_duration . "</time>\n" .
                    "\t<year>" . $year . "</year>\n" .
                    "\t<tracks>" . $songs . "</tracks>\n" .
                    "\t<songcount>" . $album->song_count . "</songcount>\n" .
                    "\t<diskcount>" . $disk . "</diskcount>\n" .
                    "\t<type>" . $album->release_type . "</type>\n" .
                    self::genre_string($album->tags) .
                    "\t<art><![CDATA[$art_url]]></art>\n" .
                    "\t<flag>" . (!$flag->get_flag($user_id, false) ? 0 : 1) . "</flag>\n" .
                    "\t<preciserating>" . ($rating->get_user_rating($user_id) ?: null) . "</preciserating>\n" .
                    "\t<rating>" . ($rating->get_user_rating($user_id) ?: null) . "</rating>\n" .
                    "\t<averagerating>" . ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                    "\t<mbid><![CDATA[" . $album->mbid . "]]></mbid>\n" .
                    "</album>\n";
        } // end foreach

        return self::output_xml($string, $full_xml);
    } // albums

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty XML document
     *
     * @param  array   $playlists Playlist id's to include
     * @param  integer $user_id
     * @return string  return xml
     */
    public static function playlists($playlists, $user_id = null)
    {
        if ((count($playlists) > self::$limit || self::$offset > 0) && self::$limit) {
            $playlists = array_slice($playlists, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_count('playlist') . "</total_count>\n";

        // Foreach the playlist ids
        foreach ($playlists as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist  = 1000000
             */
            if ((int) $playlist_id === 0) {
                $playlist = new Search((int) str_replace('smart_', '', (string) $playlist_id));
                $playlist->format();

                $playlist_name  = Search::get_name_byid(str_replace('smart_', '', (string) $playlist_id));
                $playlist_user  = ($playlist->type !== 'public') ? $playlist->f_user : $playlist->type;
                $last_count     = ((int) $playlist->last_count > 0) ? $playlist->last_count : 5000;
                $playitem_total = ($playlist->limit == 0) ? $last_count : $playlist->limit;
                $playlist_type  = $playlist->type;
                $object_type    = 'search';
            } else {
                $playlist    = new Playlist($playlist_id);
                $playlist_id = $playlist->id;
                $playlist->format();

                $playlist_name  = $playlist->name;
                $playlist_user  = $playlist->f_user;
                $playitem_total = $playlist->get_media_count('song');
                $playlist_type  = $playlist->type;
                $object_type    = 'playlist';
            }
            $rating  = new Rating($playlist_id, $object_type);
            $flag    = new Userflag($playlist_id, $object_type);
            $art_url = Art::url($playlist_id, $object_type, Core::get_request('auth'));

            // Build this element
            $string .= "<playlist id=\"$playlist_id\">\n" .
                "\t<name><![CDATA[$playlist_name]]></name>\n" .
                "\t<owner><![CDATA[$playlist_user]]></owner>\n" .
                "\t<items>$playitem_total</items>\n" .
                "\t<type>$playlist_type</type>\n" .
                "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t<flag>" . (!$flag->get_flag($user_id, false) ? 0 : 1) . "</flag>\n" .
                "\t<preciserating>" . ($rating->get_user_rating($user_id) ?: null) . "</preciserating>\n" .
                "\t<rating>" . ($rating->get_user_rating($user_id) ?: null) . "</rating>\n" .
                "\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                "</playlist>\n";
        } // end foreach

        return self::output_xml($string);
    } // playlists

    /**
     * shares
     *
     * This returns shares to the user, in a pretty xml document with the information
     *
     * @param  integer[] $shares Share id's to include
     * @return string    return xml
     */
    public static function shares($shares)
    {
        if ((count($shares) > self::$limit || self::$offset > 0) && self::$limit) {
            $shares = array_splice($shares, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_count('share') . "</total_count>\n";

        foreach ($shares as $share_id) {
            $share = new Share($share_id);
            $string .= "<share id=\"$share_id\">\n" . "\t<name><![CDATA[" . $share->getObjectName() . "]]></name>\n" . "\t<user><![CDATA[" . $share->getUserName() . "]]></user>\n" . "\t<allow_stream>" . $share->allow_stream . "</allow_stream>\n" . "\t<allow_download>" . $share->allow_download . "</allow_download>\n" . "\t<creation_date><![CDATA[" . $share->getCreationDateFormatted() . "]]></creation_date>\n" . "\t<lastvisit_date><![CDATA[" . $share->getLastVisitDateFormatted() . "]]></lastvisit_date>\n" . "\t<object_type><![CDATA[" . $share->object_type . "]]></object_type>\n" . "\t<object_id>" . $share->object_id . "</object_id>\n" . "\t<expire_days>" . $share->expire_days . "</expire_days>\n" . "\t<max_counter>" . $share->max_counter . "</max_counter>\n" . "\t<counter>" . $share->counter . "</counter>\n" . "\t<secret><![CDATA[" . $share->secret . "]]></secret>\n" . "\t<public_url><![CDATA[" . $share->public_url . "]]></public_url>\n" . "\t<description><![CDATA[" . $share->description . "]]></description>\n" . "</share>\n";
        } // end foreach

        return self::output_xml($string);
    } // shares

    /**
     * bookmarks
     *
     * This returns bookmarks to the user, in a pretty xml document with the information
     *
     * @param  integer[] $bookmarks Bookmark id's to include
     * @return string    return xml
     */
    public static function bookmarks($bookmarks)
    {
        $string = "";
        foreach ($bookmarks as $bookmark_id) {
            $bookmark = new Bookmark($bookmark_id);
            $string .= "<bookmark id=\"$bookmark_id\">\n" .
                "\t<user><![CDATA[" . $bookmark->getUserName() . "]]></user>\n" .
                "\t<object_type><![CDATA[" . $bookmark->object_type . "]]></object_type>\n" .
                "\t<object_id>" . $bookmark->object_id . "</object_id>\n" .
                "\t<position>" . $bookmark->position . "</position>\n" .
                "\t<client><![CDATA[" . $bookmark->comment . "]]></client>\n" .
                "\t<creation_date>" . $bookmark->creation_date . "</creation_date>\n" .
                "\t<update_date><![CDATA[" . $bookmark->update_date . "]]></update_date>\n" .
                "</bookmark>\n";
        } // end foreach

        return self::output_xml($string);
    } // bookmarks

    /**
     * catalogs
     *
     * This returns catalogs to the user, in a pretty xml document with the information
     *
     * @param  integer[] $catalogs group of catalog id's
     * @return string    return xml
     */
    public static function catalogs($catalogs)
    {
        if ((count($catalogs) > self::$limit || self::$offset > 0) && self::$limit) {
            $catalogs = array_splice($catalogs, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_count('catalog') . "</total_count>\n";

        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            $catalog->format();
            $string .= "<catalog id=\"$catalog_id\">\n" . "\t<name><![CDATA[" . $catalog->name . "]]></name>\n" . "\t<type><![CDATA[" . $catalog->catalog_type . "]]></type>\n" . "\t<gather_types><![CDATA[" . $catalog->gather_types . "]]></gather_types>\n" . "\t<enabled>" . $catalog->enabled . "</enabled>\n" . "\t<last_add><![CDATA[" . $catalog->f_add . "]]></last_add>\n" . "\t<last_clean><![CDATA[" . $catalog->f_clean . "]]></last_clean>\n" . "\t<last_update><![CDATA[" . $catalog->f_update . "]]></last_update>\n" . "\t<path><![CDATA[" . $catalog->f_info . "]]></path>\n" . "\t<rename_pattern><![CDATA[" . $catalog->rename_pattern . "]]></rename_pattern>\n" . "\t<sort_pattern><![CDATA[" . $catalog->sort_pattern . "]]></sort_pattern>\n" . "</catalog>\n";
        } // end foreach

        return self::output_xml($string);
    } // catalogs

    /**
     * podcasts
     *
     * This returns podcasts to the user, in a pretty xml document with the information
     *
     * @param  integer[] $podcasts Podcast id's to include
     * @param  integer   $user_id
     * @param  boolean   $episodes include the episodes of the podcast // optional
     * @return string    return xml
     */
    public static function podcasts($podcasts, $user_id = null, $episodes = false)
    {
        if ((count($podcasts) > self::$limit || self::$offset > 0) && self::$limit) {
            $podcasts = array_splice($podcasts, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_count('podcast') . "</total_count>\n";

        foreach ($podcasts as $podcast_id) {
            $podcast = new Podcast($podcast_id);
            $podcast->format();
            $rating  = new Rating($podcast_id, 'podcast');
            $flag    = new Userflag($podcast_id, 'podcast');
            $art_url = Art::url($podcast_id, 'podcast', Core::get_request('auth'));
            $string .= "<podcast id=\"$podcast_id\">\n" .
                "\t<name><![CDATA[" . $podcast->f_title . "]]></name>\n" .
                "\t<description><![CDATA[" . $podcast->description . "]]></description>\n" .
                "\t<language><![CDATA[" . $podcast->f_language . "]]></language>\n" .
                "\t<copyright><![CDATA[" . $podcast->f_copyright . "]]></copyright>\n" .
                "\t<feed_url><![CDATA[" . $podcast->feed . "]]></feed_url>\n" .
                "\t<generator><![CDATA[" . $podcast->f_generator . "]]></generator>\n" .
                "\t<website><![CDATA[" . $podcast->f_website . "]]></website>\n" .
                "\t<build_date><![CDATA[" . $podcast->f_lastbuilddate . "]]></build_date>\n" .
                "\t<sync_date><![CDATA[" . $podcast->f_lastsync . "]]></sync_date>\n" .
                "\t<public_url><![CDATA[" . $podcast->link . "]]></public_url>\n" .
                "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t<flag>" . (!$flag->get_flag($user_id, false) ? 0 : 1) . "</flag>\n" .
                "\t<preciserating>" . ($rating->get_user_rating($user_id) ?: null) . "</preciserating>\n" .
                "\t<rating>" . ($rating->get_user_rating($user_id) ?: null) . "</rating>\n" .
                "\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n";
            if ($episodes) {
                $items = $podcast->get_episodes();
                if (count($items) > 0) {
                    $string .= self::podcast_episodes($items, $user_id, false);
                }
            }
            $string .= "\t</podcast>\n";
        } // end foreach

        return self::output_xml($string);
    } // podcasts

    /**
     * podcast_episodes
     *
     * This returns podcasts to the user, in a pretty xml document with the information
     *
     * @param  integer[] $podcast_episodes Podcast_Episode id's to include
     * @param  integer   $user_id
     * @param  boolean   $full_xml whether to return a full XML document or just the node.
     * @return string    return xml
     */
    public static function podcast_episodes($podcast_episodes, $user_id = null, $full_xml = true)
    {
        if ((count($podcast_episodes) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $podcast_episodes = array_splice($podcast_episodes, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_count('podcast_episode') . "</total_count>\n" : '';

        foreach ($podcast_episodes as $episode_id) {
            $episode = new Podcast_Episode($episode_id);
            $episode->format();
            $rating  = new Rating($episode_id, 'podcast_episode');
            $flag    = new Userflag($episode_id, 'podcast_episode');
            $art_url = Art::url($episode->podcast, 'podcast', Core::get_request('auth'));
            $string .= "\t<podcast_episode id=\"$episode_id\">\n" .
                "\t\t<title><![CDATA[" . $episode->f_title . "]]></title>\n" .
                "\t\t<name><![CDATA[" . $episode->f_title . "]]></name>\n" .
                "\t\t<description><![CDATA[" . $episode->f_description . "]]></description>\n" .
                "\t\t<category><![CDATA[" . $episode->f_category . "]]></category>\n" .
                "\t\t<author><![CDATA[" . $episode->f_author . "]]></author>\n" .
                "\t\t<author_full><![CDATA[" . $episode->f_artist_full . "]]></author_full>\n" .
                "\t\t<website><![CDATA[" . $episode->f_website . "]]></website>\n" .
                "\t\t<pubdate><![CDATA[" . $episode->f_pubdate . "]]></pubdate>\n" .
                "\t\t<state><![CDATA[" . $episode->f_state . "]]></state>\n" .
                "\t\t<filelength><![CDATA[" . $episode->f_time_h . "]]></filelength>\n" .
                "\t\t<filesize><![CDATA[" . $episode->f_size . "]]></filesize>\n" .
                "\t\t<filename><![CDATA[" . $episode->f_file . "]]></filename>\n" .
                "\t\t<mime><![CDATA[" . $episode->mime . "]]></mime>\n" .
                "\t\t<public_url><![CDATA[" . $episode->link . "]]></public_url>\n" .
                "\t\t<url><![CDATA[" . $episode->play_url('', 'api', false, $user_id) . "]]></url>\n" .
                "\t\t<catalog><![CDATA[" . $episode->catalog . "]]></catalog>\n" .
                "\t\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t\t<flag>" . (!$flag->get_flag($user_id, false) ? 0 : 1) . "</flag>\n" .
                "\t\t<preciserating>" . ($rating->get_user_rating($user_id) ?: null) . "</preciserating>\n" .
                "\t\t<rating>" . ($rating->get_user_rating($user_id) ?: null) . "</rating>\n" .
                "\t\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                "\t\t<played>" . $episode->played . "</played>\n";
            $string .= "\t</podcast_episode>\n";
        } // end foreach

        return self::output_xml($string, $full_xml);
    } // podcast_episodes

    /**
     * songs
     *
     * This returns an xml document from an array of song ids.
     * (Spiffy isn't it!)
     * @param integer[] $songs
     * @param integer   $user_id
     * @param boolean   $full_xml
     * @return string   return xml
     */
    public static function songs($songs, $user_id = null, $full_xml = true)
    {
        if ((count($songs) > self::$limit || self::$offset > 0) && (self::$limit && $full_xml)) {
            $songs = array_slice($songs, self::$offset, self::$limit);
        }
        $string = ($full_xml) ? "<total_count>" . Catalog::get_count('song') . "</total_count>\n" : '';

        Song::build_cache($songs);
        Stream::set_session(Core::get_request('auth'));

        $playlist_track = 0;

        // Foreach the ids!
        foreach ($songs as $song_id) {
            $song = new Song($song_id);

            // If the song id is invalid/null
            if (!$song->id) {
                continue;
            }

            $song->format();
            $tag_string    = self::genre_string(Tag::get_top_tags('song', $song_id));
            $rating        = new Rating($song_id, 'song');
            $flag          = new Userflag($song_id, 'song');
            $show_song_art = AmpConfig::get('show_song_art', false);
            $art_object    = ($show_song_art) ? $song->id : $song->album;
            $art_type      = ($show_song_art) ? 'song' : 'album';
            $art_url       = Art::url($art_object, $art_type, Core::get_request('auth'));
            $playlist_track++;

            $string .= "<song id=\"" . $song->id . "\">\n" .
                    // Title is an alias for name
                    "\t<title><![CDATA[" . $song->title . "]]></title>\n" .
                    "\t<name><![CDATA[" . $song->title . "]]></name>\n" .
                    "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->get_artist_name() . "]]></artist>\n" .
                    "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->get_album_name() . "]]></album>\n" .
                    "\t<albumartist id=\"" . $song->albumartist . "\"><![CDATA[" . $song->get_album_artist_name() . "]]></albumartist>\n" .
                    "\t<disk><![CDATA[" . $song->disk . "]]></disk>\n" .
                    "\t<track>" . $song->track . "</track>\n";
            $string .= $tag_string .
                    "\t<filename><![CDATA[" . $song->file . "]]></filename>\n" .
                    "\t<playlisttrack>" . $playlist_track . "</playlisttrack>\n" .
                    "\t<time>" . $song->time . "</time>\n" .
                    "\t<year>" . $song->year . "</year>\n" .
                    "\t<bitrate>" . $song->bitrate . "</bitrate>\n" .
                    "\t<rate>" . $song->rate . "</rate>\n" .
                    "\t<mode><![CDATA[" . $song->mode . "]]></mode>\n" .
                    "\t<mime><![CDATA[" . $song->mime . "]]></mime>\n" .
                    "\t<url><![CDATA[" . $song->play_url('', 'api', false, $user_id) . "]]></url>\n" .
                    "\t<size>" . $song->size . "</size>\n" .
                    "\t<mbid><![CDATA[" . $song->mbid . "]]></mbid>\n" .
                    "\t<album_mbid><![CDATA[" . $song->album_mbid . "]]></album_mbid>\n" .
                    "\t<artist_mbid><![CDATA[" . $song->artist_mbid . "]]></artist_mbid>\n" .
                    "\t<albumartist_mbid><![CDATA[" . $song->albumartist_mbid . "]]></albumartist_mbid>\n" .
                    "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                    "\t<flag>" . (!$flag->get_flag($user_id, false) ? 0 : 1) . "</flag>\n" .
                    "\t<preciserating>" . ($rating->get_user_rating($user_id) ?: null) . "</preciserating>\n" .
                    "\t<rating>" . ($rating->get_user_rating($user_id) ?: null) . "</rating>\n" .
                    "\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                    "\t<playcount>" . $song->played . "</playcount>\n" .
                    "\t<catalog>" . $song->catalog . "</catalog>\n" .
                    "\t<composer><![CDATA[" . $song->composer . "]]></composer>\n" .
                    "\t<channels>" . $song->channels . "</channels>\n" .
                    "\t<comment><![CDATA[" . $song->comment . "]]></comment>\n" .
                    "\t<license><![CDATA[" . $song->f_license . "]]></license>\n" .
                    "\t<publisher><![CDATA[" . $song->label . "]]></publisher>\n" .
                    "\t<language>" . $song->language . "</language>\n" .
                    "\t<replaygain_album_gain>" . $song->replaygain_album_gain . "</replaygain_album_gain>\n" .
                    "\t<replaygain_album_peak>" . $song->replaygain_album_peak . "</replaygain_album_peak>\n" .
                    "\t<replaygain_track_gain>" . $song->replaygain_track_gain . "</replaygain_track_gain>\n" .
                    "\t<replaygain_track_peak>" . $song->replaygain_track_peak . "</replaygain_track_peak>\n" .
                    "\t<r128_album_gain>" . $song->r128_album_gain . "</r128_album_gain>\n" .
                    "\t<r128_track_gain>" . $song->r128_track_gain . "</r128_track_gain>\n";
            if (Song::isCustomMetadataEnabled()) {
                foreach ($song->getMetadata() as $metadata) {
                    $meta_name = str_replace(array(' ', '(', ')', '/', '\\', '#'), '_',
                        $metadata->getField()->getName());
                    $string .= "\t<" . $meta_name . "><![CDATA[" . $metadata->getData() . "]]></" . $meta_name . ">\n";
                }
            }

            $string .= "</song>\n";
        } // end foreach

        return self::output_xml($string, $full_xml);
    } // songs

    /**
     * videos
     *
     * This builds the xml document for displaying video objects
     *
     * @param  integer[] $videos Video id's to include
     * @param  integer   $user_id
     * @return string    return xml
     */
    public static function videos($videos, $user_id = null)
    {
        if ((count($videos) > self::$limit || self::$offset > 0) && self::$limit) {
            $videos = array_slice($videos, self::$offset, self::$limit);
        }
        $string = "<total_count>" . Catalog::get_count('video') . "</total_count>\n";

        foreach ($videos as $video_id) {
            $video = new Video($video_id);
            $video->format();
            $rating  = new Rating($video_id, 'video');
            $flag    = new Userflag($video_id, 'video');
            $art_url = Art::url($video_id, 'video', Core::get_request('auth'));

            $string .= "<video id=\"" . $video->id . "\">\n" .
                "\t<title><![CDATA[" . $video->title . "]]></title>\n" .
                "\t<name><![CDATA[" . $video->title . "]]></name>\n" .
                "\t<mime><![CDATA[" . $video->mime . "]]></mime>\n" .
                "\t<resolution><![CDATA[" . $video->f_resolution . "]]></resolution>\n" .
                "\t<size>" . $video->size . "</size>\n" .
                self::genre_string($video->tags) .
                "\t<time><![CDATA[" . $video->time . "]]></time>\n" .
                "\t<url><![CDATA[" . $video->play_url('', 'api', false, $user_id) . "]]></url>\n" .
                "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t<flag>" . (!$flag->get_flag($user_id, false) ? 0 : 1) . "</flag>\n" .
                "\t<preciserating>" . ($rating->get_user_rating($user_id) ?: null) . "</preciserating>\n" .
                "\t<rating>" . ($rating->get_user_rating($user_id) ?: null) . "</rating>\n" .
                "\t<averagerating>" . (string) ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                "</video>\n";
        } // end foreach

        return self::output_xml($string);
    } // videos

    /**
     * democratic
     *
     * This handles creating an xml document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param  array    $object_ids Object IDs
     * @param  integer  $user_id
     * @return string   return xml
     */
    public static function democratic($object_ids = array(), $user_id = null)
    {
        $democratic = Democratic::get_current_playlist();
        $string     = '';

        foreach ($object_ids as $row_id => $data) {
            $class_name = ObjectTypeToClassNameMapper::map($data['object_type']);
            $song       = new $class_name($data['object_id']);
            $song->format();

            // FIXME: This is duplicate code and so wrong, functions need to be improved
            $tag           = new Tag($song->tags['0']);
            $song->genre   = $tag->id;
            $song->f_genre = $tag->name;

            $tag_string = self::genre_string($song->tags);

            $rating = new Rating($song->id, 'song');

            $art_url = Art::url($song->album, 'album', Core::get_request('auth'));

            $string .= "<song id=\"" . $song->id . "\">\n" .
                    // Title is an alias for name
                    "\t<title><![CDATA[" . $song->title . "]]></title>\n" .
                    "\t<name><![CDATA[" . $song->title . "]]></name>\n" .
                    "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->f_artist_full . "]]></artist>\n" .
                    "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->f_album_full . "]]></album>\n" .
                    "\t<genre id=\"" . $song->genre . "\"><![CDATA[" . $song->f_genre . "]]></genre>\n" .
                    $tag_string .
                    "\t<track>" . $song->track . "</track>\n" .
                    "\t<time><![CDATA[" . $song->time . "]]></time>\n" .
                    "\t<mime><![CDATA[" . $song->mime . "]]></mime>\n" .
                    "\t<url><![CDATA[" . $song->play_url('', 'api', false, $user_id) . "]]></url>\n" .
                    "\t<size>" . $song->size . "</size>\n" .
                    "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                    "\t<preciserating>" . ($rating->get_user_rating($user_id) ?: null) . "</preciserating>\n" .
                    "\t<rating>" . ($rating->get_user_rating($user_id) ?: null) . "</rating>\n" .
                    "\t<averagerating>" . ($rating->get_average_rating() ?: null) . "</averagerating>\n" .
                    "\t<vote>" . $democratic->get_vote($row_id) . "</vote>\n" .
                    "</song>\n";
        } // end foreach

        return self::output_xml($string);
    } // democratic

    /**
     * user
     *
     * This handles creating an xml document for a user
     *
     * @param  User    $user User Object
     * @param  boolean $fullinfo
     * @return string  return xml
     */
    public static function user(User $user, $fullinfo)
    {
        $user->format();
        $string = "<user id=\"" . (string)$user->id . "\">\n" . "\t<username><![CDATA[" . $user->username . "]]></username>\n";
        if ($fullinfo) {
            $string .= "\t<auth><![CDATA[" . $user->apikey . "]]></auth>\n" .
                       "\t<email><![CDATA[" . $user->email . "]]></email>\n" .
                       "\t<access>" . (int) $user->access . "</access>\n" .
                       "\t<fullname_public>" . (int) $user->fullname_public . "</fullname_public>\n" .
                       "\t<validation><![CDATA[" . $user->validation . "]]></validation>\n" .
                       "\t<disabled>" . (int) $user->disabled . "</disabled>\n";
        }
        $string .= "\t<create_date>" . (int) $user->create_date . "</create_date>\n" .
                "\t<last_seen>" . (int) $user->last_seen . "</last_seen>\n" .
                "\t<link><![CDATA[" . $user->link . "]]></link>\n" .
                "\t<website><![CDATA[" . $user->website . "]]></website>\n" .
                "\t<state><![CDATA[" . $user->state . "]]></state>\n" .
                "\t<city><![CDATA[" . $user->city . "]]></city>\n";
        if ($user->fullname_public || $fullinfo) {
            $string .= "\t<fullname><![CDATA[" . $user->fullname . "]]></fullname>\n";
        }
        $string .= "</user>\n";

        return self::output_xml($string);
    } // user

    /**
     * users
     *
     * This handles creating an xml document for an user list
     *
     * @param  integer[] $users User identifier list
     * @return string    return xml
     */
    public static function users($users)
    {
        $string = "";
        foreach ($users as $user_id) {
            $user = new User($user_id);
            $string .= "<user id=\"" . (string)$user->id . "\">\n" . "\t<username><![CDATA[" . $user->username . "]]></username>\n" . "</user>\n";
        }

        return self::output_xml($string);
    } // users

    /**
     * shouts
     *
     * This handles creating an xml document for a shout list
     *
     * @param  integer[] $shouts Shout identifier list
     * @return string    return xml
     */
    public static function shouts($shouts)
    {
        $string = "";
        foreach ($shouts as $shout_id) {
            $shout = new Shoutbox($shout_id);
            $user  = new User($shout->user);
            $string .= "\t<shout id=\"" . $shout_id . "\">\n" . "\t\t<date>" . $shout->date . "</date>\n" . "\t\t<text><![CDATA[" . $shout->text . "]]></text>\n";
            if ($user->id) {
                $string .= "\t\t<user id=\"" . (string)$user->id . "\">\n" . "\t\t\t<username><![CDATA[" . $user->username . "]]></username>\n" . "\t\t</user>\n";
            }
            $string .= "\t</shout>\n";
        }

        return self::output_xml($string);
    } // shouts

    /**
     * @param  string  $string
     * @param  boolean $full_xml
     * @return string
     */
    public static function output_xml($string, $full_xml = true)
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
            $dom = new DOMDocument;
            // format the string
            $dom->preserveWhiteSpace = false;
            $dom->loadXML($xml);
            $dom->formatOutput = true;

            return $dom->saveXML();
        }

        return $xml;
    }

    /**
     * timeline
     *
     * This handles creating an xml document for an activity list
     *
     * @param  integer[] $activities Activity identifier list
     * @return string    return xml
     */
    public static function timeline($activities)
    {
        $string = "";
        foreach ($activities as $activity_id) {
            $activity = new Useractivity($activity_id);
            $user     = new User($activity->user);
            $string .= "\t<activity id=\"" . $activity_id . "\">\n" . "\t\t<date>" . $activity->activity_date . "</date>\n" . "\t\t<object_type><![CDATA[" . $activity->object_type . "]]></object_type>\n" . "\t\t<object_id>" . $activity->object_id . "</object_id>\n" . "\t\t<action><![CDATA[" . $activity->action . "]]></action>\n";
            if ($user->id) {
                $string .= "\t\t<user id=\"" . (string)$user->id . "\">\n" . "\t\t\t<username><![CDATA[" . $user->username . "]]></username>\n" . "\t\t</user>\n";
            }
            $string .= "\t</activity>\n";
        }

        return self::_header() . $string . self::_footer();
    } // timeline

    /**
     * rss_feed
     *
     * returns xml for rss types that aren't podcasts (Feed generation of plays/albums/etc)
     *
     * @param  array  $data Keyed array of information to RSS'ify
     * @param  string $title RSS feed title
     * @param  string $date publish date
     * @return string RSS feed xml
     */
    public static function rss_feed($data, $title, $date = null)
    {
        $string = "\t<title>$title</title>\n\t<link>" . AmpConfig::get('web_path') . "</link>\n\t";
        if (is_int($date)) {
            $string .= "<pubDate>" . date("r", (int)$date) . "</pubDate>\n";
        }

        // Pass it to the keyed array xml function
        foreach ($data as $item) {
            // We need to enclose it in an item tag
            $string .= self::keyed_array(array('item' => $item), true);
        }

        return self::_header() . $string . self::_footer();
    } // rss_feed

    /**
     * _header
     *
     * this returns a standard header, there are a few types
     * so we allow them to pass a type if they want to
     *
     * @param  string $title
     * @return string Header xml tag.
     */
    private static function _header($title = null)
    {
        switch (self::$type) {
            case 'xspf':
                $header = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n" . "<playlist version = \"1\" xmlns=\"http://xspf.org/ns/0/\">\n" . "<title>" . ($title ?: T_("Ampache XSPF Playlist")) . "</title>\n" . "<creator>" . scrub_out(AmpConfig::get('site_title')) . "</creator>\n" . "<annotation>" . scrub_out(AmpConfig::get('site_title')) . "</annotation>\n" . "<info>" . AmpConfig::get('web_path') . "</info>\n" . "<trackList>\n";
                break;
            case 'itunes':
                $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                        "<!-- XML Generated by Ampache v." . AmpConfig::get('version') . " -->\n";
                break;
            case 'rss':
                $header = "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset') . "\" ?>\n " . "<!-- RSS Generated by Ampache v." . AmpConfig::get('version') . " on " . date("r",
                        time()) . "-->\n" . "<rss version=\"2.0\">\n<channel>\n";
                break;
            default:
                $header = "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset') . "\" ?>\n<root>\n";
                break;
        } // end switch

        return $header;
    } // _header

    /**
     * _footer
     *
     * this returns the footer for this document, these are pretty boring
     *
     * @return string Footer xml tag.
     */
    private static function _footer()
    {
        switch (self::$type) {
            case 'itunes':
                $footer = "\t\t</dict>\t\n</dict>\n</plist>\n";
                break;
            case 'xspf':
                $footer = "</trackList>\n</playlist>\n";
                break;
            case 'rss':
                $footer = "\n</channel>\n</rss>\n";
                break;
            default:
                $footer = "\n</root>\n";
                break;
        } // end switch on type


        return $footer;
    }

    // _footer

    /**
     * podcast
     * @param  library_item $libitem
     * @param  integer      $user_id
     * @return string|false
     */
    public static function podcast(library_item $libitem, $user_id = null)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><rss />');
        $xml->addAttribute("xmlns:xmlns:atom", "http://www.w3.org/2005/Atom");
        $xml->addAttribute("xmlns:xmlns:itunes", "http://www.itunes.com/dtds/podcast-1.0.dtd");
        $xml->addAttribute("version", "2.0");
        $xchannel = $xml->addChild("channel");
        $xchannel->addChild("title", htmlspecialchars($libitem->get_fullname() . " Podcast"));
        //$xlink = $xchannel->addChild("atom:link", htmlentities($libitem->link));
        $libitem_type = ObjectTypeToClassNameMapper::reverseMap(get_class($libitem));
        if (Art::has_db($libitem->id, $libitem_type)) {
            $ximg = $xchannel->addChild("xmlns:itunes:image");
            $ximg->addAttribute("href", Art::url($libitem->id, $libitem_type));
        }
        $summary = $libitem->get_description();
        if (!empty($summary)) {
            $summary = htmlentities($summary);
            $xchannel->addChild("description", $summary);
            $xchannel->addChild("xmlns:itunes:summary", $summary);
        }
        $xchannel->addChild("generator", "ampache");
        $xchannel->addChild("xmlns:itunes:category", "Music");
        $owner = $libitem->get_user_owner();
        if ($owner) {
            $user_owner = new User($owner);
            $user_owner->format();
            $xowner = $xchannel->addChild("xmlns:itunes:owner");
            $xowner->addChild("xmlns:itunes:name", $user_owner->f_name);
        }

        $medias = $libitem->get_medias();
        foreach ($medias as $media_info) {
            $class_name = ObjectTypeToClassNameMapper::map($media_info['object_type']);
            $media      = new $class_name($media_info['object_id']);
            $media->format();
            $xitem = $xchannel->addChild("item");
            $xitem->addChild("title", htmlentities($media->get_fullname()));
            if ($media->f_artist) {
                $xitem->addChild("xmlns:itunes:author", $media->f_artist);
            }
            //$xmlink = $xitem->addChild("link", htmlentities($media->link));
            $xitem->addChild("guid", htmlentities($media->link));
            if ($media->addition_time) {
                $xitem->addChild("pubDate", date("r", (int)$media->addition_time));
            }
            $description = $media->get_description();
            if (!empty($description)) {
                $xitem->addChild("description", htmlentities($description));
            }
            $xitem->addChild("xmlns:itunes:duration", $media->f_time);
            if ($media->mime) {
                $surl  = $media->play_url('', 'api', false, $user_id);
                $xencl = $xitem->addChild("enclosure");
                $xencl->addAttribute("type", (string)$media->mime);
                $xencl->addAttribute("length", (string)$media->size);
                $xencl->addAttribute("url", $surl);
            }
        }

        $xmlstr = $xml->asXml();
        // Format xml output
        $dom = new DOMDocument();
        if ($dom->loadXML($xmlstr, LIBXML_PARSEHUGE) !== false) {
            $dom->formatOutput = true;

            return $dom->saveXML();
        } else {
            return $xmlstr;
        }
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
}
