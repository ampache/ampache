<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * XML_Data Class
 *
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls
 *
 */
class XML_Data
{
    // This is added so that we don't pop any webservers
    private static $limit  = 5000;
    private static $offset = 0;
    private static $type   = '';

    /**
     * constructor
     *
     * We don't use this, as its really a static class
     */
    private function __construct()
    {
        // Rien a faire
    }
    // constructor

    /**
     * set_offset
     *
     * This takes an int and changes the offset
     *
     * @param    integer    $offset    (description here...)
     * @return    void
     */
    public static function set_offset($offset)
    {
        self::$offset = (int) $offset;
    }
    // set_offset

    /**
     * set_limit
     *
     * This sets the limit for any ampache transactions
     *
     * @param    integer    $limit    (description here...)
     * @return    false|null
     */
    public static function set_limit($limit)
    {
        if (!$limit) {
            return false;
        }

        if (strtolower((string) $limit) == "none") {
            self::$limit = null;
        } else {
            self::$limit = (int) ($limit);
        }
    }
    // set_limit

    /**
     * set_type
     *
     * This sets the type of XML_Data we are working on
     *
     * @param    string    $type    XML_Data type
     * @return    false|null
     */
    public static function set_type($type)
    {
        if (!in_array($type, array('rss', 'xspf', 'itunes'))) {
            return false;
        }

        self::$type = $type;
    }
    // set_type

    /**
     * error
     *
     * This generates a standard XML Error message
     * nothing fancy here...
     *
     * @param    integer    $code    Error code
     * @param    string    $string    Error message
     * @return    string    return error message xml
     */
    public static function error($code, $string)
    {
        $xml_string = "\t<error code=\"$code\"><![CDATA[$string]]></error>";

        return self::output_xml($xml_string);
    }
    // error

    /**
     * success
     *
     * This generates a standard XML Success message
     * nothing fancy here...
     *
     * @param    string    $string    success message
     * @return    string    return success message xml
     */
    public static function success($string)
    {
        $xml_string = "\t<success code=\"1\"><![CDATA[$string]]></success>";

        return self::output_xml($xml_string);
    }
    // success

    /**
     * header
     *
     * This returns the header
     *
     * @see    _header()
     * @return    string    return xml
     */
    public static function header($title = null)
    {
        return self::_header($title);
    }
    // header

    /**
     * footer
     *
     * This returns the footer
     *
     * @see    _footer()
     * @return    string    return xml
     */
    public static function footer()
    {
        return self::_footer();
    }
    // footer

    /**
     * tags_string
     *
     * This returns the formatted 'tags' string for an xml document
     * @input array $tags
     * @return string
     */
    private static function tags_string($tags)
    {
        $string = '';

        if (is_array($tags)) {
            $atags = array();
            foreach ($tags as $tag_id => $data) {
                if (array_key_exists($data['id'], $atags)) {
                    $atags[$data['id']]['count'] ++;
                } else {
                    $atags[$data['id']] = array('name' => $data['name'],
                        'count' => 1);
                }
            }

            foreach ($atags as $tag => $data) {
                $string .= "\t<tag id=\"" . $tag . "\" " .
                        "count=\"" . $data['count'] . "\" " .
                        "><![CDATA[" . $data['name'] . "]]></tag>\n";
            }
        }

        return $string;
    }
    // tags_string

    /**
     * playlist_song_tracks_string
     *
     * This returns the formatted 'playlistTrack' string for an xml document
     *
     * @param Song $song
     * @param int[] $playlist_data
     * @return string
     */
    private static function playlist_song_tracks_string($song, $playlist_data)
    {
        if (empty($playlist_data)) {
            return "";
        }
        foreach ($playlist_data as $playlist) {
            if ($playlist["object_id"] == $song->id) {
                return "\t<playlisttrack>" . $playlist["track"] . "</playlisttrack>\n";
            }
        }

        return "";
    }
    // playlist_song_tracks_string

    /**
     * output_xml_from_array
     * This takes a one dimensional array and creates a XML document from it. For
     * use primarily by the ajax mojo.
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
            $string = '<?xml version="1.0" encoding="utf-8" ?>' .
                "\n<root>\n" . $string . "</root>\n";
        }

        return UI::clean_utf8($string);
    }
    } // output_from_array

    /**
     * keyed_array
     *
     * This will build an xml document from a key'd array,
     *
     * @param    array    $array    (description here...)
     * @param    string|boolean    $callback    (description here...)
     * @return    string    return xml
     */
    public static function keyed_array($array, $callback = '')
    {
        $string = '';

        // Foreach it
        foreach ($array as $key => $value) {
            $attribute = '';
            // See if the key has attributes
            if (is_array($value) && isset($value['<attributes>'])) {
                $attribute = ' ' . $value['<attributes>'];
                $key       = $value['value'];
            }

            // If it's an array, run again
            if (is_array($value)) {
                $value = self::keyed_array($value, 1);
                $string .= "<$key$attribute>\n$value\n</$key>\n";
            } else {
                $string .= "\t<$key$attribute><![CDATA[$value]]></$key>\n";
            }
        } // end foreach

        if (!$callback) {
            $string = self::output_xml($string);
        }

        return $string;
    }
    // keyed_array

    /**
     * indexes
     *
     * This takes an array of artists and then returns a pretty xml document with the information
     * we want
     *
     * @param    array    $objects     (description here...)
     * @param    string   $object_type 'artist'|'album'|'song'|'playlist'
     * @param    bool     $full_xml    whether to return a full XML document or just the node.
     * @return   string   return xml
     */
    public static function indexes($objects, $object_type, $full_xml = true)
    {
        $string = "<total_count>" . count($objects) . "</total_count>\n";

        if (count($objects) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $objects = array_splice($objects, self::$offset, self::$limit);
            } else {
                $objects = array_splice($objects, self::$offset);
            }
        }

        foreach ($objects as $object_id) {
            // 'artist'|'album'|'song'|'playlist'
            if ($object_type == 'artist') {
                $artist = new Artist($object_id);
                $artist->format();
                $albums = $artist->get_albums(null, true);
                $string .= "<$object_type id=\"" . $object_id . "\">\n" .
                        "\t<name><![CDATA[" . $artist->f_full_name . "]]></name>\n";
                foreach ($albums as $album_id) {
                    if ($album_id) {
                        $album = new Album($album_id[0]);
                        $album->format();
                        $string .= "\t\t<album id=\"" . $album_id[0] .
                                '"><![CDATA[' . $album->f_name .
                                "]]></album>\n";
                    }
                }
                $string .= "</$object_type>\n";
            }
            if ($object_type == 'album') {
                $album = new Album($object_id);
                $album->format();
                $string .= "<$object_type id=\"" . $object_id . "\">\n" .
                        "\t<name><![CDATA[" . $album->f_name . "]]></name>\n" .
                        "\t\t<artist id=\"" . $album->album_artist . "\"><![CDATA[" . $album->album_artist_name . "]]></artist>\n" .
                        "</$object_type>\n";
            }
            if ($object_type == 'song') {
                $song = new Song($object_id);
                $song->format();
                $string .= "<$object_type id=\"" . $object_id . "\">\n" .
                        "\t<title><![CDATA[" . $song->title . "]]></title>\n" .
                        "\t<name><![CDATA[" . $song->f_title . "]]></name>\n" .
                        "\t\t<artist id=\"" . $song->artist .
                        '"><![CDATA[' . $song->get_artist_name() .
                        "]]></artist>\n" .
                        "\t\t<album id=\"" . $song->album .
                        '"><![CDATA[' . $song->get_album_name() .
                        "]]></album>\n" .
                        "</$object_type>\n";
            }
            if ($object_type == 'playlist') {
                if (str_replace('smart_', '', (string) $object_id) === (string) $object_id) {
                    $playlist     = new Playlist($object_id);
                    $playlist->format();

                    $playlist_name  = $playlist->name;
                    $playitem_total = $playlist->get_media_count('song');
                } else {
                    $playlist     = new Search(str_replace('smart_', '', (string) $object_id));
                    $playlist->format();

                    $playlist_name  = Search::get_name_byid(str_replace('smart_', '', (string) $object_id));
                    $playitem_total = ($playlist->limit == 0) ? 5000 : $playlist->limit;
                }
                // don't allow unlimited smartlists or empty playlists into xml
                if ((int) $playitem_total > 0) {
                    $songs = $playlist->get_items();
                    $string .= "<$object_type id=\"" . $object_id . "\">\n" .
                            "\t<name><![CDATA[" . $playlist_name . "]]></name>\n";
                    foreach ($songs as $song_id) {
                        if ($song_id['object_type'] == 'song') {
                            $string .= "\t\t<playlisttrack>" . $song_id['object_id'] . "</playlisttrack>\n";
                        }
                    }
                    $string .= "</$object_type>\n";
                }
            }
        } // end foreach objects

        return self::output_xml($string, $full_xml);
    }
    // indexes

    /**
     * tags
     *
     * This returns tags to the user, in a pretty xml document with the information
     *
     * @param    array    $tags    (description here...)
     * @return    string    return xml
     */
    public static function tags($tags)
    {
        $string = "<total_count>" . count($tags) . "</total_count>\n";

        if (count($tags) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $tags = array_splice($tags, self::$offset, self::$limit);
            } else {
                $tags = array_splice($tags, self::$offset);
            }
        }

        foreach ($tags as $tag_id) {
            $tag    = new Tag($tag_id);
            $counts = $tag->count();
            $string .= "<tag id=\"$tag_id\">\n" .
                    "\t<name><![CDATA[$tag->name]]></name>\n" .
                    "\t<albums>" . (int) ($counts['album']) . "</albums>\n" .
                    "\t<artists>" . (int) ($counts['artist']) . "</artists>\n" .
                    "\t<songs>" . (int) ($counts['song']) . "</songs>\n" .
                    "\t<videos>" . (int) ($counts['video']) . "</videos>\n" .
                    "\t<playlists>" . (int) ($counts['playlist']) . "</playlists>\n" .
                    "\t<stream>" . (int) ($counts['live_stream']) . "</stream>\n" .
                    "</tag>\n";
        } // end foreach

        return self::output_xml($string);
    }
    // tags

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty xml document with the information
     * we want
     *
     * @param    array    $artists    (description here...)
     * @param    array    $include    Array of other items to include.
     * @param    bool     $full_xml  whether to return a full XML document or just the node.
     * @param    integer $user_id
     * @return    string    return xml
     */
    public static function artists($artists, $include = [], $full_xml = true, $user_id = false)
    {
        if ($include == null || $include == '') {
            $include = array();
        }
        if (is_string($include)) {
            $include = explode(',', $include);
        }
        $string = "<total_count>" . count($artists) . "</total_count>\n";

        if (count($artists) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $artists = array_splice($artists, self::$offset, self::$limit);
            } else {
                $artists = array_splice($artists, self::$offset);
            }
        }

        Rating::build_cache('artist', $artists);

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            $artist->format();

            $rating     = new Rating($artist_id, 'artist');
            $flag       = new Userflag($artist_id, 'artist');
            $tag_string = self::tags_string($artist->tags);

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $artist_id . '&object_type=artist&auth=' . scrub_out(Core::get_request('auth'));

            // Handle includes
            if (in_array("albums", $include)) {
                $albums = self::albums($artist->get_albums(), $include, false);
            } else {
                $albums = ($artist->albums ?: 0);
            }
            if (in_array("songs", $include)) {
                $songs = self::songs($artist->get_songs(), array(), false);
            } else {
                $songs = ($artist->songs ?: 0);
            }

            $string .= "<artist id=\"" . $artist->id . "\">\n" .
                    "\t<name><![CDATA[" . $artist->f_full_name . "]]></name>\n" .
                    $tag_string .
                    "\t<albums>" . $albums . "</albums>\n" .
                    "\t<songs>" . $songs . "</songs>\n" .
                    "\t<art><![CDATA[$art_url]]></art>\n" .
                    "\t<flag>" . ($flag->get_flag($user_id, false) ? 1 : 0) . "</flag>\n" .
                    "\t<preciserating>" . ($rating->get_user_rating($user_id) ?: 0) . "</preciserating>\n" .
                    "\t<rating>" . ($rating->get_user_rating($user_id) ?: 0) . "</rating>\n" .
                    "\t<averagerating>" . ($rating->get_average_rating() ?: 0) . "</averagerating>\n" .
                    "\t<mbid>" . $artist->mbid . "</mbid>\n" .
                    "\t<summary><![CDATA[" . $artist->summary . "]]></summary>\n" .
                    "\t<yearformed>" . $artist->yearformed . "</yearformed>\n" .
                    "\t<placeformed><![CDATA[" . $artist->placeformed . "]]></placeformed>\n" .
                    "</artist>\n";
        } // end foreach artists

        return self::output_xml($string, $full_xml);
    }
    // artists

    /**
     * albums
     *
     * This echos out a standard albums XML document, it pays attention to the limit
     *
     * @param    integer[]    $albums    (description here...)
     * @param    array    $include    Array of other items to include.
     * @param    bool     $full_xml  whether to return a full XML document or just the node.
     * @return    string    return xml
     */
    public static function albums($albums, $include = [], $full_xml = true, $user_id = false)
    {
        if ($include == null || $include == '') {
            $include = array();
        }
        if (is_string($include)) {
            $include = explode(',', $include);
        }
        $string = "<total_count>" . count($albums) . "</total_count>\n";

        if (count($albums) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $albums = array_splice($albums, self::$offset, self::$limit);
            } else {
                $albums = array_splice($albums, self::$offset);
            }
        }

        Rating::build_cache('album', $albums);

        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $album->format();

            $disk   = $album->disk;
            $rating = new Rating($album_id, 'album');
            $flag   = new Userflag($album_id, 'album');

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $album->id . '&object_type=album&auth=' . scrub_out(Core::get_request('auth'));

            $string .= "<album id=\"" . $album->id . "\">\n" .
                    "\t<name><![CDATA[" . $album->name . "]]></name>\n";

            // Do a little check for artist stuff
            if ($album->album_artist_name != "") {
                $string .= "\t<artist id=\"$album->artist_id\"><![CDATA[$album->album_artist_name]]></artist>\n";
            } elseif ($album->artist_count != 1) {
                $string .= "\t<artist id=\"0\"><![CDATA[Various]]></artist>\n";
            } else {
                $string .= "\t<artist id=\"$album->artist_id\"><![CDATA[$album->artist_name]]></artist>\n";
            }

            // Handle includes
            if (in_array("songs", $include)) {
                $songs = self::songs($album->get_songs(), array(), false);
            } else {
                $songs = $album->song_count;
            }

            //count multiple disks
            if ($album->allow_group_disks) {
                $disk = (count($album->album_suite) <= 1) ? $album->disk : count($album->album_suite);
            }

            $string .= "\t<year>" . $album->year . "</year>\n" .
                    "\t<tracks>" . $songs . "</tracks>\n" .
                    "\t<disk>" . $disk . "</disk>\n" .
                    self::tags_string($album->tags) .
                    "\t<art><![CDATA[$art_url]]></art>\n" .
                    "\t<flag>" . ($flag->get_flag($user_id, false) ? 1 : 0) . "</flag>\n" .
                    "\t<preciserating>" . $rating->get_user_rating($user_id) . "</preciserating>\n" .
                    "\t<rating>" . $rating->get_user_rating($user_id) . "</rating>\n" .
                    "\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n" .
                    "\t<mbid>" . $album->mbid . "</mbid>\n" .
                    "</album>\n";
        } // end foreach

        return self::output_xml($string, $full_xml);
    }
    // albums

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty XML document
     *
     * @param    array    $playlists    (description here...)
     * @return    string    return xml
     */
    public static function playlists($playlists)
    {
        if (count($playlists) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $playlists = array_slice($playlists, self::$offset, self::$limit);
            } else {
                $playlists = array_slice($playlists, self::$offset);
            }
        }
        $string = "<total_count>" . count($playlists) . "</total_count>\n";

        // Foreach the playlist ids
        foreach ($playlists as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist  = 1000000
             */
            if (str_replace('smart_', '', (string) $playlist_id) === (string) $playlist_id) {
                $playlist     = new Playlist($playlist_id);
                $playlist_id  = $playlist->id;
                $playlist->format();

                $playlist_name  = $playlist->name;
                $playlist_user  = $playlist->f_user;
                $playitem_total = $playlist->get_media_count('song');
                $playlist_type  = $playlist->type;
            } else {
                $playlist     = new Search(str_replace('smart_', '', (string) $playlist_id));
                $playlist->format();

                $playlist_name  = Search::get_name_byid(str_replace('smart_', '', (string) $playlist_id));
                if ($playlist->type !== 'public') {
                    $playlist_user  = $playlist->f_user;
                } else {
                    $playlist_user  = $playlist->type;
                }
                $last_count     = ((int) $playlist->last_count > 0) ? $playlist->last_count : 5000;
                $playitem_total = ($playlist->limit == 0) ? $last_count : $playlist->limit;
                $playlist_type  = $playlist->type;
            }
            // Build this element
            $string .= "<playlist id=\"$playlist_id\">\n" .
                    "\t<name><![CDATA[$playlist_name]]></name>\n" .
                    "\t<owner><![CDATA[$playlist_user]]></owner>\n" .
                    "\t<items>$playitem_total</items>\n" .
                    "\t<type>$playlist_type</type>\n" .
                    "</playlist>\n";
        } // end foreach

        return self::output_xml($string);
    }
    // playlists

    /**
     * songs
     *
     * This returns an xml document from an array of song ids.
     * (Spiffy isn't it!)
     * @param  integer[] $songs
     * @return string    return xml
     */
    public static function songs($songs, $playlist_data = array(), $full_xml = true, $user_id = false)
    {
        $string = "<total_count>" . count($songs) . "</total_count>\n";

        if (count($songs) > self::$limit || self::$offset > 0) {
            if (null !== self::$limit) {
                $songs = array_slice($songs, self::$offset, self::$limit);
            } else {
                $songs = array_slice($songs, self::$offset);
            }
        }

        Song::build_cache($songs);
        Stream::set_session(Core::get_request('auth'));

        // Foreach the ids!
        foreach ($songs as $song_id) {
            $song = new Song($song_id);

            // If the song id is invalid/null
            if (!$song->id) {
                continue;
            }

            $song->format();
            $track_string = self::playlist_song_tracks_string($song, $playlist_data);
            $tag_string   = self::tags_string(Tag::get_top_tags('song', $song_id));
            $rating       = new Rating($song_id, 'song');
            $flag         = new Userflag($song_id, 'song');
            $art_url      = Art::url($song->album, 'album', Core::get_request('auth'));

            $string .= "<song id=\"" . $song->id . "\">\n" .
                    // Title is an alias for name
                    "\t<title><![CDATA[" . $song->title . "]]></title>\n" .
                    "\t<name><![CDATA[" . $song->title . "]]></name>\n" .
                    "\t<artist id=\"" . $song->artist .
                    '"><![CDATA[' . $song->get_artist_name() .
                    "]]></artist>\n" .
                    "\t<album id=\"" . $song->album .
                    '"><![CDATA[' . $song->get_album_name() .
                    "]]></album>\n";
            if ($song->albumartist) {
                $string .= "\t<albumartist id=\"" . $song->albumartist .
                        "\"><![CDATA[" . $song->get_album_artist_name() . "]]></albumartist>\n";
            }
            $string .= $tag_string .
                    "\t<filename><![CDATA[" . $song->file . "]]></filename>\n" .
                    "\t<track>" . $song->track . "</track>\n" .
                    $track_string .
                    "\t<time>" . $song->time . "</time>\n" .
                    "\t<year>" . $song->year . "</year>\n" .
                    "\t<bitrate>" . $song->bitrate . "</bitrate>\n" .
                    "\t<rate>" . $song->rate . "</rate>\n" .
                    "\t<mode>" . $song->mode . "</mode>\n" .
                    "\t<mime>" . $song->mime . "</mime>\n" .
                    "\t<url><![CDATA[" . Song::play_url($song->id, '', 'api', false, $user_id, true) . "]]></url>\n" .
                    "\t<size>" . $song->size . "</size>\n" .
                    "\t<mbid>" . $song->mbid . "</mbid>\n" .
                    "\t<album_mbid>" . $song->album_mbid . "</album_mbid>\n" .
                    "\t<artist_mbid>" . $song->artist_mbid . "</artist_mbid>\n" .
                    "\t<albumartist_mbid>" . $song->albumartist_mbid . "</albumartist_mbid>\n" .
                    "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                    "\t<flag>" . ($flag->get_flag($user_id, false) ? 1 : 0) . "</flag>\n" .
                    "\t<preciserating>" . ($rating->get_user_rating($user_id) ?: 0) . "</preciserating>\n" .
                    "\t<rating>" . ($rating->get_user_rating($user_id) ?: 0) . "</rating>\n" .
                    "\t<averagerating>" . (string) ($rating->get_average_rating() ?: 0) . "</averagerating>\n" .
                    "\t<composer><![CDATA[" . $song->composer . "]]></composer>\n" .
                    "\t<channels>" . $song->channels . "</channels>\n" .
                    "\t<comment><![CDATA[" . $song->comment . "]]></comment>\n";

            $string .= "\t<publisher><![CDATA[" . $song->label . "]]></publisher>\n"
                    . "\t<language>" . $song->language . "</language>\n"
                    . "\t<replaygain_album_gain>" . $song->replaygain_album_gain . "</replaygain_album_gain>\n"
                    . "\t<replaygain_album_peak>" . $song->replaygain_album_peak . "</replaygain_album_peak>\n"
                    . "\t<replaygain_track_gain>" . $song->replaygain_track_gain . "</replaygain_track_gain>\n"
                    . "\t<replaygain_track_peak>" . $song->replaygain_track_peak . "</replaygain_track_peak>\n";
            foreach ($song->tags as $tag) {
                $string .= "\t<genre><![CDATA[" . $tag['name'] . "]]></genre>\n";
            }

            $string .= "</song>\n";
        } // end foreach

        return self::output_xml($string, $full_xml);
    }
    // songs

    /**
     * videos
     *
     * This builds the xml document for displaying video objects
     *
     * @param    array    $videos    (description here...)
     * @return   string   return xml
     */
    public static function videos($videos, $user_id = false)
    {
        $string = '<total_count>' . count($videos) . "</total_count>\n";

        if (count($videos) > self::$limit or self::$offset > 0) {
            if (null !== self::$limit) {
                $videos = array_slice($videos, self::$offset, self::$limit);
            } else {
                $videos = array_slice($videos, self::$offset);
            }
        }

        foreach ($videos as $video_id) {
            $video = new Video($video_id);
            $video->format();

            $string .= "<video id=\"" . $video->id . "\">\n" .
                    // Title is an alias for name
                    "\t<title><![CDATA[" . $video->title . "]]></title>\n" .
                    "\t<name><![CDATA[" . $video->title . "]]></name>\n" .
                    "\t<mime><![CDATA[" . $video->mime . "]]></mime>\n" .
                    "\t<resolution>" . $video->f_resolution . "</resolution>\n" .
                    "\t<size>" . $video->size . "</size>\n" .
                    self::tags_string($video->tags) .
                    "\t<url><![CDATA[" . Video::play_url($video->id, '', 'api', false, $user_id) . "]]></url>\n" .
                    "</video>\n";
        } // end foreach

        return self::output_xml($string);
    }
    // videos

    /**
     * democratic
     *
     * This handles creating an xml document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param    integer[]  $object_ids    Object IDs
     * @return   string     return xml
     */
    public static function democratic($object_ids = array(), $user_id = false)
    {
        $democratic = Democratic::get_current_playlist();
        $string     = '';

        foreach ($object_ids as $row_id => $data) {
            $song = new $data['object_type']($data['object_id']);
            $song->format();

            //FIXME: This is duplicate code and so wrong, functions need to be improved
            $tag           = new Tag($song->tags['0']);
            $song->genre   = $tag->id;
            $song->f_genre = $tag->name;

            $tag_string = self::tags_string($song->tags);

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
                    "\t<time>" . $song->time . "</time>\n" .
                    "\t<mime>" . $song->mime . "</mime>\n" .
                    "\t<url><![CDATA[" . Song::play_url($song->id, '', 'api', false, $user_id, true) . "]]></url>\n" .
                    "\t<size>" . $song->size . "</size>\n" .
                    "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                    "\t<preciserating>" . $rating->get_user_rating($user_id) . "</preciserating>\n" .
                    "\t<rating>" . $rating->get_user_rating($user_id) . "</rating>\n" .
                    "\t<averagerating>" . (string) $rating->get_average_rating() . "</averagerating>\n" .
                    "\t<vote>" . $democratic->get_vote($row_id) . "</vote>\n" .
                    "</song>\n";
        } // end foreach

        return self::output_xml($string);
    }
    // democratic

    /**
     * user
     *
     * This handles creating an xml document for a user
     *
     * @param  User   $user User
     * @param  bool   $fullinfo
     * @return string return xml
     */
    public static function user(User $user, $fullinfo)
    {
        $user->format();
        $string = "<user id=\"" . (string) $user->id . "\">\n" .
                  "\t<username><![CDATA[" . $user->username . "]]></username>\n";
        if ($fullinfo) {
            $string .= "\t<auth><![CDATA[" . $user->apikey . "]]></auth>\n" .
                       "\t<email><![CDATA[" . $user->email . "]]></email>\n" .
                       "\t<access><![CDATA[" . (string) $user->access . "]]></access>\n" .
                       "\t<fullname_public><![CDATA[" . (string) $user->fullname_public . "]]></fullname_public>\n" .
                       "\t<validation><![CDATA[" . $user->validation . "]]></validation>\n" .
                       "\t<disabled><![CDATA[" . (string) $user->disabled . "]]></disabled>\n";
        }
        $string .= "\t<create_date><![CDATA[" . (string) $user->create_date . "]]></create_date>\n" .
                "\t<last_seen><![CDATA[" . (string) $user->last_seen . "]]></last_seen>\n" .
                "\t<link><![CDATA[" . $user->link . "]]></link>\n" .
                "\t<website><![CDATA[" . $user->website . "]]></website>\n" .
                "\t<state><![CDATA[" . $user->state . "]]></state>\n" .
                "\t<city><![CDATA[" . $user->city . "]]></city>\n";
        if ($user->fullname_public || $fullinfo) {
            $string .= "\t<fullname><![CDATA[" . $user->fullname . "]]></fullname>\n";
        }
        $string .= "</user>\n";

        return self::output_xml($string);
    }
    // user

    /**
     * users
     *
     * This handles creating an xml document for an user list
     *
     * @param    int[]    $users    User identifier list
     * @return    string    return xml
     */
    public static function users($users)
    {
        $string = "<users>\n";
        foreach ($users as $user_id) {
            $user = new User($user_id);
            $string .= "\t<username><![CDATA[" . $user->username . "]]></username>\n";
        }
        $string .= "</users>\n";

        return self::output_xml($string);
    }
    // users

    /**
     * shouts
     *
     * This handles creating an xml document for a shout list
     *
     * @param    int[]    $shouts    Shout identifier list
     * @return    string    return xml
     */
    public static function shouts($shouts)
    {
        $string = "<shouts>\n";
        foreach ($shouts as $shout_id) {
            $shout = new Shoutbox($shout_id);
            $shout->format();
            $user = new User($shout->user);
            $string .= "\t<shout id=\"" . $shout_id . "\">\n" .
                    "\t\t<date>" . $shout->date . "</date>\n" .
                    "\t\t<text><![CDATA[" . $shout->text . "]]></text>\n";
            if ($user->id) {
                $string .= "\t\t<username><![CDATA[" . $user->username . "]]></username>\n";
            }
            $string .= "\t</shout>\n";
        }
        $string .= "</shouts>\n";

        return self::output_xml($string);
    } // shouts

    public static function output_xml($string, $full_xml = true)
    {
        $xml = "";
        if ($full_xml) {
            $xml .= self::_header();
        }
        $xml .= UI::clean_utf8($string);
        if ($full_xml) {
            $xml .= self::_footer();
        }

        return $xml;
    }

    /**
     * timeline
     *
     * This handles creating an xml document for an activity list
     *
     * @param    int[]    $activities    Activity identifier list
     * @return    string    return xml
     */
    public static function timeline($activities)
    {
        $string = "<timeline>\n";
        foreach ($activities as $aid) {
            $activity = new Useractivity($aid);
            $user     = new User($activity->user);
            $string .= "\t<activity id=\"" . $aid . "\">\n" .
                    "\t\t<date>" . $activity->activity_date . "</date>\n" .
                    "\t\t<object_type><![CDATA[" . $activity->object_type . "]]></object_type>\n" .
                    "\t\t<object_id>" . $activity->object_id . "</object_id>\n" .
                    "\t\t<action><![CDATA[" . $activity->action . "]]></action>\n";
            if ($user->id) {
                $string .= "\t\t<username><![CDATA[" . $user->username . "]]></username>";
            }
            $string .= "\t</activity>n";
        }
        $string .= "</timeline>\n";

        $final = self::_header() . $string . self::_footer();

        return $final;
    }
    // timeline

    /**
     * rss_feed
     *
     * (description here...)
     *
     * @param    array    $data    (description here...)
     * @param    string    $title    RSS feed title
     * @param    string    $date    publish date
     * @return    string    RSS feed xml
     */
    public static function rss_feed($data, $title, $date = null)
    {
        $string = "\t<title>$title</title>\n\t<link>" . AmpConfig::get('web_path') . "</link>\n\t";
        if (is_int($date)) {
            $string .= "<pubDate>" . date("r", (int) $date) . "</pubDate>\n";
        }

        // Pass it to the keyed array xml function
        foreach ($data as $item) {
            // We need to enclose it in an item tag
            $string .= self::keyed_array(array('item' => $item), 1);
        }

        $final = self::_header() . $string . self::_footer();

        return $final;
    }
    // rss_feed

    /**
     * _header
     *
     * this returns a standard header, there are a few types
     * so we allow them to pass a type if they want to
     *
     * @return    string    Header xml tag.
     */
    private static function _header($title = null)
    {
        switch (self::$type) {
            case 'xspf':
                $header = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n" .
                        "<playlist version = \"1\" xmlns=\"http://xspf.org/ns/0/\">\n" .
                        "<title>" . ($title ?: T_("Ampache XSPF Playlist")) . "</title>\n" .
                        "<creator>" . scrub_out(AmpConfig::get('site_title')) . "</creator>\n" .
                        "<annotation>" . scrub_out(AmpConfig::get('site_title')) . "</annotation>\n" .
                        "<info>" . AmpConfig::get('web_path') . "</info>\n" .
                        "<trackList>\n";
                break;
            case 'itunes':
                $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                        "<!-- XML Generated by Ampache v." . AmpConfig::get('version') . " -->\n";
                "<!DOCTYPE plist PUBLIC \"-//Apple Computer//DTD PLIST 1.0//EN\"\n" .
                        "\"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n" .
                        "<plist version=\"1.0\">\n" .
                        "<dict>\n" .
                        "       <key>Major Version</key><integer>1</integer>\n" .
                        "       <key>Minor Version</key><integer>1</integer>\n" .
                        "       <key>Application Version</key><string>7.0.2</string>\n" .
                        "       <key>Features</key><integer>1</integer>\n" .
                        "       <key>Show Content Ratings</key><true/>\n" .
                        "       <key>Tracks</key>\n" .
                        "       <dict>\n";
                break;
            case 'rss':
                $header = "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset') . "\" ?>\n " .
                        "<!-- RSS Generated by Ampache v." . AmpConfig::get('version') . " on " . date("r", time()) . "-->\n" .
                        "<rss version=\"2.0\">\n<channel>\n";
                break;
            default:
                $header = "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset') . "\" ?>\n<root>\n";
                break;
        } // end switch

        return $header;
    }
    // _header

    /**
     * _footer
     *
     * this returns the footer for this document, these are pretty boring
     *
     * @return    string    Footer xml tag.
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

    public static function podcast(library_item $libitem, $user_id = false)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><rss />');
        $xml->addAttribute("xmlns:xmlns:atom", "http://www.w3.org/2005/Atom");
        $xml->addAttribute("xmlns:xmlns:itunes", "http://www.itunes.com/dtds/podcast-1.0.dtd");
        $xml->addAttribute("version", "2.0");
        $xchannel = $xml->addChild("channel");
        $xchannel->addChild("title", htmlspecialchars($libitem->get_fullname() . " Podcast"));
        //$xlink = $xchannel->addChild("atom:link", htmlentities($libitem->link));
        if (Art::has_db($libitem->id, get_class($libitem))) {
            $ximg = $xchannel->addChild("xmlns:itunes:image");
            $ximg->addAttribute("href", Art::url($libitem->id, get_class($libitem)));
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
            $media = new $media_info['object_type']($media_info['object_id']);
            $media->format();
            $xitem = $xchannel->addChild("item");
            $xitem->addChild("title", htmlentities($media->get_fullname()));
            if ($media->f_artist) {
                $xitem->addChild("xmlns:itunes:author", $media->f_artist);
            }
            //$xmlink = $xitem->addChild("link", htmlentities($media->link));
            $xitem->addChild("guid", htmlentities($media->link));
            if ($media->addition_time) {
                $xitem->addChild("pubDate", date("r", (int) $media->addition_time));
            }
            $description = $media->get_description();
            if (!empty($description)) {
                $xitem->addChild("description", htmlentities($description));
            }
            $xitem->addChild("xmlns:itunes:duration", $media->f_time);
            if ($media->mime) {
                $surl  = $media_info['object_type']::play_url($media_info['object_id'], '', 'api', false, $user_id);
                $xencl = $xitem->addChild("enclosure");
                $xencl->addAttribute("type", (string) $media->mime);
                $xencl->addAttribute("length", (string) $media->size);
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
}

// XML_Data
