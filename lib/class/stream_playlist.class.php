<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
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
 * Stream_Playlist Class
 *
 * This class is used to generate the Playlists and pass them on
 * For localplay this actually just sends the commands to the localplay
 * module in question.
 */

class Stream_Playlist
{
    public $id;
    public $urls  = array();
    public $user;

    /**
     * Stream_Playlist constructor
     * If an ID is passed, it should be a stream session ID.
     */
    public function __construct($id = null)
    {
        if ($id != -1) {
            if ($id) {
                Stream::set_session($id);
            }

            $this->id = Stream::$session;

            if (!Session::exists('stream', $this->id)) {
                debug_event('stream_playlist', 'Session::exists failed', 2);
                return false;
            }

            $this->user = intval($GLOBALS['user']->id);

            $sql = 'SELECT * FROM `stream_playlist` WHERE `sid` = ? ORDER BY `id`';
            $db_results = Dba::read($sql, array($this->id));

            while ($row = Dba::fetch_assoc($db_results)) {
                $this->urls[] = new Stream_URL($row);
            }
        }

        return true;
    }

    private function _add_url($url)
    {
        debug_event("stream_playlist.class.php", "Adding url {".json_encode($url)."}...", 5);

        $this->urls[] = $url;
        $sql = 'INSERT INTO `stream_playlist` ';

        $fields = array();
        $fields[] = '`sid`';
        $values = array();
        $values[] = $this->id;
        $holders = array();
        $holders[] = '?';

        foreach ($url->properties as $field) {
            if ($url->$field) {
                $fields[] = '`' . $field . '`';
                $holders[] = '?';
                $values[] = $url->$field;
            }
        }
        $sql .= '(' . implode(', ', $fields) . ') ';
        $sql .= 'VALUES(' . implode(', ', $holders) . ')';

        return Dba::write($sql, $values);
    }

    public static function gc()
    {
        $sql = 'DELETE FROM `stream_playlist` USING `stream_playlist` ' .
            'LEFT JOIN `session` ON `session`.`id`=`stream_playlist`.`sid` ' .
            'WHERE `session`.`id` IS NULL';
        return Dba::write($sql);
    }

    /**
     * media_to_urlarray
     * Formats the URL and media information and adds it to the object
     */
    public static function media_to_urlarray($media, $additional_params='')
    {
        $urls = array();
        foreach ($media as $medium) {
            $url = array();

            if ($medium['custom_play_action']) {
                $additional_params .= "&custom_play_action=" . $medium['custom_play_action'];
            }

            if ($_SESSION['iframe']['subtitle']) {
                $additional_params .= "&subtitle=" . $_SESSION['iframe']['subtitle'];
            }

            $type = $medium['object_type'];
            $object_id = $medium['object_id'];
            $url['type'] = $type;

            $object = new $type($object_id);
            $object->format();
            // Don't add disabled media objects to the stream playlist
            // Playing a disabled media return a 404 error that could make failed the player (mpd ...)
            if (!isset($object->enabled) || make_bool($object->enabled)) {
                //FIXME: play_url shouldn't be static
                $url['url'] = $type::play_url($object->id, $additional_params);

                $api_session = (AmpConfig::get('require_session')) ? Stream::$session : false;

                // Set a default which can be overridden
                $url['author'] = 'Ampache';
                $url['time'] = $object->time;
                switch ($type) {
                    case 'song':
                        $url['title'] = $object->title;
                        $url['author'] = $object->f_artist_full;
                        $url['info_url'] = $object->f_link;
                        $url['image_url'] = Art::url($object->album, 'album', $api_session);
                        $url['album'] = $object->f_album_full;
                    break;
                    case 'video':
                        $url['title'] = 'Video - ' . $object->title;
                        $url['author'] = $object->f_artist_full;
                        $url['resolution'] = $object->f_resolution;
                    break;
                    case 'live_stream':
                        $url['title'] = 'Radio - ' . $object->name;
                        if (!empty($object->site_url)) {
                            $url['title'] .= ' (' . $object->site_url . ')';
                        }
                        $url['codec'] = $object->codec;
                    break;
                    case 'song_preview':
                        $url['title'] = $object->title;
                        $url['author'] = $object->f_artist_full;
                    break;
                    case 'channel':
                        $url['title'] = $object->name;
                    break;
                    case 'random':
                        $url['title'] = 'Random URL';
                    break;
                    default:
                        $url['title'] = 'URL-Add';
                        $url['time'] = -1;
                    break;
                }

                $urls[] = new Stream_URL($url);
            }
        }

        return $urls;
    }

    public static function check_autoplay_append()
    {
        // For now, only iframed web player support media append in the currently played playlist
        return ((AmpConfig::get('ajax_load') && AmpConfig::get('play_type') == 'web_player') ||
            AmpConfig::get('play_type') == 'localplay'
        );
    }

    public function generate_playlist($type, $redirect = false)
    {
        if (!count($this->urls)) {
            debug_event('stream_playlist', 'Error: Empty URL array for ' . $this->id, 2);
            return false;
        }

        debug_event('stream_playlist', 'Generating a {'.$type.'} object...', 5);

        $ext = $type;
        switch ($type) {
            case 'download':
            case 'democratic':
            case 'localplay':
            case 'web_player':
                // These are valid, but witchy
                $ct = "";
                $redirect = false;
                unset($ext);
            break;
            case 'asx':
                $ct = 'video/x-ms-wmv';
            break;
            case 'pls':
                $ct = 'audio/x-scpls';
            break;
            case 'ram':
                $ct = 'audio/x-pn-realaudio ram';
            break;
            case 'simple_m3u':
                $ext = 'm3u';
                $ct = 'audio/x-mpegurl';
            break;
            case 'xspf':
                $ct = 'application/xspf+xml';
            break;
            case 'hls':
                $ext = 'm3u8';
                $ct = 'application/vnd.apple.mpegurl';
            break;
            case 'm3u':
            default:
                // Assume M3U if the pooch is screwed
                $ext = $type = 'm3u';
                $ct = 'audio/x-mpegurl';
            break;
        }

        if ($redirect) {
            // Our ID is the SID, so we always want to include it
            AmpConfig::set('require_session', true, true);
            header('Location: ' . Stream::get_base_url() . 'uid=' . scrub_out($this->user) . '&type=playlist&playlist_type=' . scrub_out($type));
            exit;
        }

        if (isset($ext)) {
            header('Cache-control: public');
            header('Content-Disposition: filename=ampache_playlist.' . $ext);
            header('Content-Type: ' . $ct . ';');
        }

        $this->{'create_' . $type}();
    }

    /**
     * add
     * Adds an array of media
     */
    public function add($media = array(), $additional_params = '')
    {
        $urls = $this->media_to_urlarray($media, $additional_params);
        foreach ($urls as $url) {
            $this->_add_url($url);
        }
    }

    /**
     * add_urls
     * Add an array of urls. This is used for things that aren't coming
     * from media objects
     */
    public function add_urls($urls = array())
    {
        if (!is_array($urls)) { return false; }

        foreach ($urls as $url) {
            $this->_add_url(new Stream_URL(array(
                'url' => $url,
                'title' => 'URL-Add',
                'author' => 'Ampache',
                'time' => '-1'
            )));
        }
    }

    /**
     * create_simplem3u
     * this creates a simple m3u without any of the extended information
     */
    public function create_simple_m3u()
    {
        foreach ($this->urls as $url) {
            echo $url->url . "\n";
        }

    } // simple_m3u

    /**
     * create_m3u
     * creates an m3u file, this includes the EXTINFO and as such can be
     * large with very long playlists
     */
    public function create_m3u()
    {
        echo "#EXTM3U\n";

        $i = 0;
        foreach ($this->urls as $url) {
            echo '#EXTINF:' . $url->time, ',' . $url->author . ' - ' . $url->title . "\n";
            echo $url->url . "\n";
            $i++;
        }

    } // create_m3u

    /**
      * create_pls
     */
    public function create_pls()
    {
        echo "[playlist]\n";
        echo 'NumberOfEntries=' . count($this->urls) . "\n";
        $i = 0;
        foreach ($this->urls as $url) {
            $i++;
            echo 'File' . $i . '='. $url->url . "\n";
            echo 'Title' . $i . '=' . $url->author . ' - ' .
                $url->title . "\n";
            echo 'Length' . $i . '=' . $url->time . "\n";
        }

        echo "Version=2\n";
    } // create_pls

    /**
     * create_asx
     * This should really only be used if all of the content is ASF files.
     */
    public function create_asx()
    {
        echo '<ASX VERSION="3.0" BANNERBAR="auto">' . "\n";
        echo "<TITLE>Ampache ASX Playlist</TITLE>\n";
        echo '<PARAM NAME="Encoding" VALUE="utf-8" />' . "\n";

        foreach ($this->urls as $url) {
            echo "<ENTRY>\n";
            echo '<TITLE>' . scrub_out($url->title) . "</TITLE>\n";
            echo '<AUTHOR>' . scrub_out($url->author) . "</AUTHOR>\n";
            //FIXME: duration looks hacky and wrong
            echo "\t\t" . '<DURATION VALUE="00:00:' . $url->time . '" />' . "\n";
            echo "\t\t" . '<PARAM NAME="Album" Value="' . scrub_out($url->album) . '" />' . "\n";
            echo "\t\t" . '<PARAM NAME="Composer" Value="' . scrub_out($url->author) . '" />' . "\n";
            echo "\t\t" . '<PARAM NAME="Prebuffer" Value="false" />' . "\n";
            echo '<REF HREF="' . $url->url . '" />' . "\n";
            echo "</ENTRY>\n";
        }

        echo "</ASX>\n";

    } // create_asx

    /**
     * create_xspf
     */
    public function create_xspf()
    {
        $result = "";
        foreach ($this->urls as $url) {
            $xml = array();

            $xml['track'] = array(
                'title' => $url->title,
                'creator' => $url->author,
                'duration' => $url->time * 1000,
                'location' => $url->url,
                'identifier' => $url->url
            );
            if ($url->type == 'video') {
                $xml['track']['meta'] =
                    array(
                        'attribute' => 'rel="provider"',
                        'value' => 'video'
                    );
            }
            if ($url->info_url) {
                $xml['track']['info'] = $url->info_url;
            }
            if ($url->image_url) {
                $xml['track']['image'] = $url->image_url;
            }
            if ($url->album) {
                $xml['track']['album'] = $url->album;
            }

            $result .= XML_Data::keyed_array($xml, true);

        } // end foreach

        XML_Data::set_type('xspf');
        echo XML_Data::header();
        echo $result;
        echo XML_Data::footer();

    } // create_xspf

    public function create_hls()
    {
        $ssize = 10;
        echo "#EXTM3U\n";
        echo "#EXT-X-TARGETDURATION:" . $ssize . "\n";
        echo "#EXT-X-VERSION:1\n";
        echo "#EXT-X-ALLOW-CACHE:NO\n";
        echo "#EXT-X-MEDIA-SEQUENCE:0\n";
        echo "#EXT-X-PLAYLIST-TYPE:VOD\n";   // Static list of segments

        foreach ($this->urls as $url) {
            $soffset = 0;
            $segment = 0;
            while ($soffset < $url->time) {
                $type = $url->type;
                $size = (($soffset + $ssize) <= $url->time) ? $ssize : ($url->time - $soffset);
                $additional_params = '&transcode_to=ts&segment=' . $segment;
                echo "#EXTINF:" . $size . ",\n";
                $purl = Stream_URL::parse($url->url);
                $id = $purl['id'];

                unset($purl['id']);
                unset($purl['ssid']);
                unset($purl['type']);
                unset($purl['base_url']);
                unset($purl['uid']);
                unset($purl['name']);

                foreach ($purl as $key => $value) {
                    $additional_params .= '&' . $key . '=' . $value;
                }

                $hu = $type::play_url($id, $additional_params);
                echo $hu . "\n";
                $soffset += $size;
                $segment++;
            }
        }

        echo "#EXT-X-ENDLIST\n\n";
    }

    /**
     * create_web_player
     *
     * Creates an web player.
     */
    public function create_web_player()
    {
        if (AmpConfig::get("ajax_load")) {
            require AmpConfig::get('prefix') . '/templates/create_web_player_embedded.inc.php';
        } else {
            require AmpConfig::get('prefix') . '/templates/create_web_player.inc.php';
        }

    }  // create_web_player

    /**
     * create_localplay
     * This calls the Localplay API to add the URLs and then start playback
     */
    public function create_localplay()
    {
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();
        $append = $_REQUEST['append'];
        if (!$append) {
            $localplay->delete_all();
        }
        foreach ($this->urls as $url) {
            $localplay->add_url($url);
        }
        if (!$append) {
            $localplay->play();
        }

    } // create_localplay

    /**
     * create_democratic
     *
     * This 'votes' on the songs; it inserts them into a tmp_playlist with user
     * set to -1.
     */
    public function create_democratic()
    {
        $democratic = Democratic::get_current_playlist();
        $democratic->set_parent();
        $items = array();

        foreach ($this->urls as $url) {
            $data = Stream_URL::parse($url->url);
            $items[] = array($data['type'], $data['id']);
        }

        $democratic->add_vote($items);
    }

    /**
     * create_download
     * This prompts for a download of the song
     */
    private function create_download()
    {
        // There should only be one here...
        if (count($this->urls) != 1) {
            debug_event('stream_playlist', 'Download called, but $urls contains ' . json_encode($this->urls), 2);
        }

        // Header redirect baby!
        $url = current($this->urls);
        $url = Stream_URL::add_options($url->url, '&action=download');
        header('Location: ' . $url);
        exit;
    } //create_download

    /**
     * create_ram
     *this functions creates a RAM file for use by Real Player
     */
    public function create_ram()
    {
        foreach ($this->urls as $url) {
            echo $url->url . "\n";
        }
    } // create_ram
}
