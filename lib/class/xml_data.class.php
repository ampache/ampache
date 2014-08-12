<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
    private static $limit = 5000;
    private static $offset = 0;
    private static $type = '';

    /**
     * constructor
     *
     * We don't use this, as its really a static class
     */
    private function __construct()
    {
        // Rien a faire

    } // constructor

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
        $offset = intval($offset);
        self::$offset = $offset;

    } // set_offset

    /**
     * set_limit
     *
     * This sets the limit for any ampache transactions
     *
     * @param    integer    $limit    (description here...)
     * @return    void
     */
    public static function set_limit($limit)
    {
        if (!$limit) { return false; }

        $limit = intval($limit);
        self::$limit = $limit;

    } // set_limit

    /**
     * set_type
     *
     * This sets the type of XML_Data we are working on
     *
     * @param    string    $type    XML_Data type
     * @return    void
     */
    public static function set_type($type)
    {
        if (!in_array($type,array('rss','xspf','itunes'))) { return false; }

        self::$type = $type;

    } // set_type

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
    public static function error($code,$string)
    {
        $string = self::_header() . "\t<error code=\"$code\"><![CDATA[$string]]></error>" . self::_footer();
        return $string;

    } // error

    /**
     * single_string
     *
     * This takes two values, first the key second the string
     *
     * @param    string    $key    (description here...)
     * @param    string    $string    xml data
     * @return    string    return xml
     */
    public static function single_string($key, $string='')
    {
        $final = self::_header();
        if (!empty($string)) {
            $final .= "\t<$key><![CDATA[$string]]></$key>";
        } else {
            $final .= "\t<$key />";
        }
        $final .= self::_footer();

        return $final;

    } // single_string

    /**
      * header
     *
     * This returns the header
     *
     * @see    _header()
     * @return    string    return xml
     */
    public static function header()
    {
        return self::_header();

    } // header

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

    } // footer

    /**
     * tags_string
     *
     * This returns the formatted 'tags' string for an xml document
     *
     */
    private static function tags_string($tags)
    {
        $string = '';

        if (is_array($tags)) {

            $atags = array();
            foreach ($tags as $tag_id => $data) {
                if (array_key_exists($data['id'], $atags)) {
                    $atags[$data['id']]['count']++;
                } else {
                    $atags[$data['id']] = array('name' => $data['name'],
                        'count' => 1);
                }
            }

            foreach ($atags as $id => $data) {
                $string .= "\t<tag id=\"" . $id . "\" " .
                "count=\"" . $data['count'] . "\" " .
                "><![CDATA[" . $data['name'] . "]]></tag>\n";
            }
        }

        return $string;

    } // tags_string

    /**
     * keyed_array
     *
     * This will build an xml document from a key'd array,
     *
     * @param    array    $array    (description here...)
     * @param    boolean    $callback    (description here...)
     * @return    string    return xml
     */
    public static function keyed_array($array,$callback='')
    {
        $string = '';

        // Foreach it
        foreach ($array as $key=>$value) {
            $attribute = '';
            // See if the key has attributes
            if (is_array($value) AND isset($value['<attributes>'])) {
                $attribute = ' ' . $value['<attributes>'];
                $key = $value['value'];
            }

            // If it's an array, run again
            if (is_array($value)) {
                $value = self::keyed_array($value,1);
                $string .= "<$key$attribute>\n$value\n</$key>\n";
            } else {
                $string .= "\t<$key$attribute><![CDATA[$value]]></$key>\n";
            }

        } // end foreach

        if (!$callback) {
            $string = self::_header() . $string . self::_footer();
        }

        return $string;

    } // keyed_array

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
        if (count($tags) > self::$limit OR self::$offset > 0) {
            $tags = array_splice($tags,self::$offset,self::$limit);
        }

        $string = '';

        foreach ($tags as $tag_id) {
            $tag = new Tag($tag_id);
            $counts = $tag->count();
            $string .= "<tag id=\"$tag_id\">\n" .
                    "\t<name><![CDATA[$tag->name]]></name>\n" .
                    "\t<albums>" . intval($counts['album']) . "</albums>\n" .
                    "\t<artists>" . intval($counts['artist']) . "</artists>\n" .
                    "\t<songs>" . intval($counts['song']) . "</songs>\n" .
                    "\t<videos>" . intval($counts['video']) . "</videos>\n" .
                    "\t<playlists>" . intval($counts['playlist']) . "</playlists>\n" .
                    "\t<stream>" . intval($counts['live_stream']) . "</stream>\n" .
                    "</tag>\n";
        } // end foreach

        $final = self::_header() . $string . self::_footer();

        return $final;

    } // tags

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty xml document with the information
     * we want
     *
     * @param    array    $artists    (description here...)
     * @return    string    return xml
     */
    public static function artists($artists)
    {
        if (count($artists) > self::$limit OR self::$offset > 0) {
            $artists = array_splice($artists,self::$offset,self::$limit);
        }

        $string = '';

        Rating::build_cache('artist',$artists);

        foreach ($artists as $artist_id) {
            $artist = new Artist($artist_id);
            $artist->format();

            $rating = new Rating($artist_id,'artist');
            $tag_string = self::tags_string($artist->tags);

            $string .= "<artist id=\"" . $artist->id . "\">\n" .
                    "\t<name><![CDATA[" . $artist->f_full_name . "]]></name>\n" .
                    $tag_string .
                    "\t<albums>" . $artist->albums . "</albums>\n" .
                    "\t<songs>" . $artist->songs . "</songs>\n" .
                    "\t<preciserating>" . $rating->get_user_rating() . "</preciserating>\n" .
                    "\t<rating>" . $rating->get_user_rating() . "</rating>\n" .
                    "\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n" .
                    "\t<mbid>" . $artist->mbid . "</mbid>\n" .
                    "\t<summary>" . $artist->summary . "</summary>\n" .
                    "\t<yearformed>" . $artist->summary . "</yearformed>\n" .
                    "\t<placeformed>" . $artist->summary . "</placeformed>\n" .
                    "</artist>\n";
        } // end foreach artists

        $final = self::_header() . $string . self::_footer();

        return $final;

    } // artists

    /**
     * albums
     *
     * This echos out a standard albums XML document, it pays attention to the limit
     *
     * @param    array    $albums    (description here...)
     * @return    string    return xml
     */
    public static function albums($albums)
    {
        if (count($albums) > self::$limit OR self::$offset > 0) {
            $albums = array_splice($albums,self::$offset,self::$limit);
        }

        Rating::build_cache('album',$albums);

        $string = "";
        foreach ($albums as $album_id) {
            $album = new Album($album_id);
            $album->format();

            $rating = new Rating($album_id,'album');

            // Build the Art URL, include session
            $art_url = AmpConfig::get('web_path') . '/image.php?object_id=' . $album->id . '&object_type=album&auth=' . scrub_out($_REQUEST['auth']);

            $string .= "<album id=\"" . $album->id . "\">\n" .
                    "\t<name><![CDATA[" . $album->name . "]]></name>\n";

            // Do a little check for artist stuff
            if ($album->artist_count != 1) {
                $string .= "\t<artist id=\"0\"><![CDATA[Various]]></artist>\n";
            } else {
                $string .= "\t<artist id=\"$album->artist_id\"><![CDATA[$album->artist_name]]></artist>\n";
            }

            $string .= "\t<year>" . $album->year . "</year>\n" .
                    "\t<tracks>" . $album->song_count . "</tracks>\n" .
                    "\t<disk>" . $album->disk . "</disk>\n" .
                    self::tags_string($album->tags) .
                    "\t<art><![CDATA[$art_url]]></art>\n" .
                    "\t<preciserating>" . $rating->get_user_rating() . "</preciserating>\n" .
                    "\t<rating>" . $rating->get_user_rating() . "</rating>\n" .
                    "\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n" .
                    "\t<mbid>" . $album->mbid . "</mbid>\n" .
                    "</album>\n";
        } // end foreach

        $final = self::_header() . $string . self::_footer();

        return $final;

    } // albums

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
        if (count($playlists) > self::$limit OR self::$offset > 0) {
            $playlists = array_slice($playlists,self::$offset,self::$limit);
        }

        $string = '';

        // Foreach the playlist ids
        foreach ($playlists as $playlist_id) {
            $playlist = new Playlist($playlist_id);
            $playlist->format();
            $item_total = $playlist->get_song_count();

            // Build this element
            $string .= "<playlist id=\"$playlist->id\">\n" .
                "\t<name><![CDATA[$playlist->name]]></name>\n" .
                "\t<owner><![CDATA[$playlist->f_user]]></owner>\n" .
                "\t<items>$item_total</items>\n" .
                "\t<type>$playlist->type</type>\n" .
                "</playlist>\n";


        } // end foreach

        // Build the final and then send her off
        $final = self::_header() . $string . self::_footer();

        return $final;

    } // playlists

    /**
     * songs
     *
     * This returns an xml document from an array of song ids.
     * (Spiffy isn't it!)
     */
    public static function songs($songs)
    {
        if (count($songs) > self::$limit OR self::$offset > 0) {
            $songs = array_slice($songs, self::$offset, self::$limit);
        }

        Song::build_cache($songs);
        Stream::set_session($_REQUEST['auth']);

        $string = "";
        // Foreach the ids!
        foreach ($songs as $song_id) {
            $song = new Song($song_id);

            // If the song id is invalid/null
            if (!$song->id) { continue; }

            $tag_string = self::tags_string(Tag::get_top_tags('song', $song_id));
            $rating = new Rating($song_id, 'song');
            $art_url = Art::url($song->album, 'album', $_REQUEST['auth']);

            $string .= "<song id=\"" . $song->id . "\">\n" .
                "\t<title><![CDATA[" . $song->title . "]]></title>\n" .
                "\t<artist id=\"" . $song->artist .
                    '"><![CDATA[' . $song->get_artist_name() .
                    "]]></artist>\n" .
                "\t<album id=\"" . $song->album .
                    '"><![CDATA[' . $song->get_album_name().
                    "]]></album>\n" .
                $tag_string .
                "\t<filename><![CDATA[" . $song->file . "]]></filename>\n" .
                "\t<track>" . $song->track . "</track>\n" .
                "\t<time>" . $song->time . "</time>\n" .
                "\t<year>" . $song->year . "</year>\n" .
                "\t<bitrate>" . $song->bitrate . "</bitrate>\n".
                "\t<mode>" . $song->mode . "</mode>\n".
                "\t<mime>" . $song->mime . "</mime>\n" .
                "\t<url><![CDATA[" . Song::play_url($song->id) . "]]></url>\n" .
                "\t<size>" . $song->size . "</size>\n".
                "\t<mbid>" . $song->mbid . "</mbid>\n".
                "\t<album_mbid>" . $song->album_mbid . "</album_mbid>\n".
                "\t<artist_mbid>" . $song->artist_mbid . "</artist_mbid>\n".
                "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                "\t<preciserating>" . $rating->get_user_rating() . "</preciserating>\n" .
                "\t<rating>" . $rating->get_user_rating() . "</rating>\n" .
                "\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n" .
                "</song>\n";

        } // end foreach

        return self::_header() . $string . self::_footer();

    } // songs

    /**
     * videos
     *
     * This builds the xml document for displaying video objects
     *
     * @param    array    $videos    (description here...)
     * @return    string    return xml
     */
    public static function videos($videos)
    {
        if (count($videos) > self::$limit OR self::$offset > 0) {
            $videos = array_slice($videos,self::$offset,self::$limit);
        }

        $string = '';
        foreach ($videos as $video_id) {
            $video = new Video($video_id);
            $video->format();

            $string .= "<video id=\"" . $video->id . "\">\n" .
                    "\t<title><![CDATA[" . $video->title . "]]></title>\n" .
                    "\t<mime><![CDATA[" . $video->mime . "]]></mime>\n" .
                    "\t<resolution>" . $video->f_resolution . "</resolution>\n" .
                    "\t<size>" . $video->size . "</size>\n" .
                    self::tags_string($video->tags) .
                    "\t<url><![CDATA[" . Video::play_url($video->id) . "]]></url>\n" .
                    "</video>\n";

        } // end foreach

        $final = self::_header() . $string . self::_footer();

        return $final;

    } // videos

    /**
     * democratic
     *
     * This handles creating an xml document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param    array    $object_ids    Object IDs
     * @return    string    return xml
     */
    public static function democratic($object_ids=array())
    {
        if (!is_array($object_ids)) { $object_ids = array(); }

        $democratic = Democratic::get_current_playlist();

        $string = '';

        foreach ($object_ids as $row_id=>$data) {
            $song = new $data['object_type']($data['object_id']);
            $song->format();

            //FIXME: This is duplicate code and so wrong, functions need to be improved
            $tag = new Tag($song->tags['0']);
            $song->genre = $tag->id;
            $song->f_genre = $tag->name;

            $tag_string = self::tags_string($song->tags);

            $rating = new Rating($song->id,'song');

            $art_url = Art::url($song->album, 'album', $_REQUEST['auth']);

            $string .= "<song id=\"" . $song->id . "\">\n" .
                    "\t<title><![CDATA[" . $song->title . "]]></title>\n" .
                    "\t<artist id=\"" . $song->artist . "\"><![CDATA[" . $song->f_artist_full . "]]></artist>\n" .
                    "\t<album id=\"" . $song->album . "\"><![CDATA[" . $song->f_album_full . "]]></album>\n" .
                    "\t<genre id=\"" . $song->genre . "\"><![CDATA[" . $song->f_genre . "]]></genre>\n" .
                    $tag_string .
                    "\t<track>" . $song->track . "</track>\n" .
                    "\t<time>" . $song->time . "</time>\n" .
                    "\t<mime>" . $song->mime . "</mime>\n" .
                    "\t<url><![CDATA[" . Song::play_url($song->id) . "]]></url>\n" .
                    "\t<size>" . $song->size . "</size>\n" .
                    "\t<art><![CDATA[" . $art_url . "]]></art>\n" .
                    "\t<preciserating>" . $rating->get_user_rating() . "</preciserating>\n" .
                    "\t<rating>" . $rating->get_user_rating() . "</rating>\n" .
                    "\t<averagerating>" . $rating->get_average_rating() . "</averagerating>\n" .
                    "\t<vote>" . $democratic->get_vote($row_id) . "</vote>\n" .
                    "</song>\n";

        } // end foreach

        $final = self::_header() . $string . self::_footer();

        return $final;

    } // democratic

    /**
     * rss_feed
     *
     * (description here...)
     *
     * @param    array    $data    (descriptiong here...)
     * @param    string    $title    RSS feed title
     * @param    string    $description    (not use yet?)
     * @param    string    $date    publish date
     * @return    string    RSS feed xml
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function rss_feed($data,$title,$description,$date)
    {
        $string = "\t<title>$title</title>\n\t<link>" . AmpConfig::get('web_path') . "</link>\n\t" .
            "<pubDate>" . date("r",$date) . "</pubDate>\n";

        // Pass it to the keyed array xml function
        foreach ($data as $item) {
            // We need to enclose it in an item tag
            $string .= self::keyed_array(array('item'=>$item),1);
        }

        $final = self::_header() . $string . self::_footer();

        return $final;

    } // rss_feed

    /**
     * _header
     *
     * this returns a standard header, there are a few types
     * so we allow them to pass a type if they want to
     *
     * @return    string    Header xml tag.
     */
    private static function _header()
    {
        switch (self::$type) {
            case 'xspf':
                $header = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n" .
                        "<playlist version = \"1\" xmlns=\"http://xspf.org/ns/0/\">\n " .
                        "<title>Ampache XSPF Playlist</title>\n" .
                        "<creator>" . scrub_out(AmpConfig::get('site_title')) . "</creator>\n" .
                        "<annotation>" . scrub_out(AmpConfig::get('site_title')) . "</annotation>\n" .
                        "<info>". AmpConfig::get('web_path') ."</info>\n" .
                        "<trackList>\n";
            break;
            case 'itunes':
                $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
                "<!-- XML Generated by Ampache v." .  AmpConfig::get('version') . " -->\n";
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
                    "<!-- RSS Generated by Ampache v." . AmpConfig::get('version') . " on " . date("r",time()) . "-->\n" .
                    "<rss version=\"2.0\">\n<channel>\n";
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

    } // _footer

} // XML_Data
