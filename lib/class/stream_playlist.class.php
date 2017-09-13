<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
    
    public $title;

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

            $this->id = Stream::get_session();

            if (!Session::exists('stream', $this->id)) {
                debug_event('stream_playlist', 'Session::exists failed', 2);

                return false;
            }

            $this->user = intval($GLOBALS['user']->id);

            $sql        = 'SELECT * FROM `stream_playlist` WHERE `sid` = ? ORDER BY `id`';
            $db_results = Dba::read($sql, array($this->id));

            while ($row = Dba::fetch_assoc($db_results)) {
                $this->urls[] = new Stream_URL($row);
            }
        }

        return true;
    }

    private function _add_url($url)
    {
        debug_event("stream_playlist.class.php", "Adding url {" . json_encode($url) . "}...", 5);

        $this->urls[] = $url;
        $sql          = 'INSERT INTO `stream_playlist` ';

        $fields    = array();
        $fields[]  = '`sid`';
        $values    = array();
        $values[]  = $this->id;
        $holders   = array();
        $holders[] = '?';

        foreach ($url->properties as $field) {
            if ($url->$field) {
                $fields[]  = '`' . $field . '`';
                $holders[] = '?';
                $values[]  = $url->$field;
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
            $surl = self::media_to_url($medium, $additional_params);
            if ($surl != null) {
                $urls[] = $surl;
            }
        }

        return $urls;
    }
    
    /**
     * media_to_url
     */
    public static function media_to_url($media, $additional_params='', $urltype='web')
    {
        $type      = $media['object_type'];
        $object_id = $media['object_id'];
        $object    = new $type($object_id);
        $object->format();
        
        if ($media['custom_play_action']) {
            $additional_params .= "&custom_play_action=" . $media['custom_play_action'];
        }

        if ($_SESSION['iframe']['subtitle']) {
            $additional_params .= "&subtitle=" . $_SESSION['iframe']['subtitle'];
        }
        
        return self::media_object_to_url($object, $additional_params, $urltype);
    }
    
    /**
     * media_object_to_url
     */
    public static function media_object_to_url($object, $additional_params='', $urltype='web')
    {
        $surl = null;
        $url  = array();
        
        $type        = strtolower(get_class($object));
        $url['type'] = $type;
        
        // Don't add disabled media objects to the stream playlist
        // Playing a disabled media return a 404 error that could make failed the player (mpd ...)
        if (!isset($object->enabled) || make_bool($object->enabled)) {
            if ($urltype == 'file') {
                $url['url'] = $object->file;
                // Relative path
                if (!empty($additional_params) && strpos($url['url'], $additional_params) === 0) {
                    $url['url'] = substr($url['url'], strlen($additional_params));
                    if (strlen($url['url']) < 1) {
                        return null;
                    }
                    if ($url['url'][0] == DIRECTORY_SEPARATOR) {
                        $url['url'] = substr($url['url'], 1);
                    }
                }
            } else {
                //FIXME: play_url shouldn't be static
                $url['url'] = $type::play_url($object->id, $additional_params);
            }

            $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : false;

            // Set a default which can be overridden
            $url['author'] = 'Ampache';
            $url['time']   = $object->time;
            switch ($type) {
                case 'song':
                    $url['title']     = $object->title;
                    $url['author']    = $object->f_artist_full;
                    $url['info_url']  = $object->f_link;
                    $url['image_url'] = Art::url($object->album, 'album', $api_session, (AmpConfig::get('ajax_load') ? 3 : 4));
                    $url['album']     = $object->f_album_full;
                    $url['track_num'] = $object->f_track;
                break;
                case 'video':
                    $url['title']      = 'Video - ' . $object->title;
                    $url['author']     = $object->f_artist_full;
                    $url['resolution'] = $object->f_resolution;
                break;
                case 'live_stream':
                    $url['title'] = 'Radio - ' . $object->name;
                    if (!empty($object->site_url)) {
                        $url['title'] .= ' (' . $object->site_url . ')';
                    }
                    $url['codec']     = $object->codec;
                    $url['image_url'] = Art::url($object->id, 'live_stream', $api_session, (AmpConfig::get('ajax_load') ? 3 : 4));
                break;
                case 'song_preview':
                    $url['title']  = $object->title;
                    $url['author'] = $object->f_artist_full;
                break;
                case 'channel':
                    $url['title'] = $object->name;
                break;
                case 'podcast_episode':
                    $url['title']     = $object->f_title;
                    $url['author']    = $object->f_podcast;
                    $url['info_url']  = $object->f_link;
                    $url['image_url'] = Art::url($object->podcast, 'podcast', $api_session, (AmpConfig::get('ajax_load') ? 3 : 4));
                break;
                case 'random':
                    $url['title'] = 'Random URL';
                break;
                default:
                    $url['title'] = 'URL-Add';
                    $url['time']  = -1;
                break;
            }

            $surl = new Stream_URL($url);
        }
        
        return $surl;
    }

    public static function check_autoplay_append()
    {
        // For now, only iframed web player support media append in the currently played playlist
        return ((AmpConfig::get('ajax_load') && AmpConfig::get('play_type') == 'web_player') ||
            AmpConfig::get('play_type') == 'localplay'
        );
    }

    public static function check_autoplay_next()
    {
        // Currently only supported for web player
        return (AmpConfig::get('ajax_load') && AmpConfig::get('play_type') == 'web_player');
    }

    public function generate_playlist($type, $redirect = false)
    {
        if (!count($this->urls)) {
            debug_event('stream_playlist', 'Error: Empty URL array for ' . $this->id, 2);

            return false;
        }

        debug_event('stream_playlist', 'Generating a {' . $type . '} object...', 5);

        $ext = $type;
        switch ($type) {
            case 'download':
            case 'democratic':
            case 'localplay':
            case 'web_player':
                // These are valid, but witchy
                $ct       = "";
                $redirect = false;
                unset($ext);
            break;
            case 'asx':
                $ct = 'video/x-ms-asf';
            break;
            case 'pls':
                $ct = 'audio/x-scpls';
            break;
            case 'ram':
                $ct = 'audio/x-pn-realaudio ram';
            break;
            case 'simple_m3u':
                $ext = 'm3u';
                $ct  = 'audio/x-mpegurl';
            break;
            case 'xspf':
                $ct = 'application/xspf+xml';
            break;
            case 'hls':
                $ext = 'm3u8';
                $ct  = 'application/vnd.apple.mpegurl';
            break;
            case 'm3u':
            default:
                // Assume M3U if the pooch is screwed
                $ext = $type = 'm3u';
                $ct  = 'audio/x-mpegurl';
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
        if (!is_array($urls)) {
            return false;
        }

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
     * get_m3u_string
     * creates an m3u file, this includes the EXTINFO and as such can be
     * large with very long playlists
     */
    public function get_m3u_string()
    {
        $ret = "#EXTM3U\n";

        $i = 0;
        foreach ($this->urls as $url) {
            $ret .= '#EXTINF:' . $url->time . ',' . $url->author . ' - ' . $url->title . "\n";
            $ret .= $url->url . "\n";
            $i++;
        }

        return $ret;
    } // get_m3u_string

    public function create_m3u()
    {
        echo $this->get_m3u_string();
    }

    /**
      * get_pls_string
     */
    public function get_pls_string()
    {
        $ret = "[playlist]\n";
        $ret .= 'NumberOfEntries=' . count($this->urls) . "\n";
        $i = 0;
        foreach ($this->urls as $url) {
            $i++;
            $ret .= 'File' . $i . '=' . $url->url . "\n";
            $ret .= 'Title' . $i . '=' . $url->author . ' - ' .
                $url->title . "\n";
            $ret .= 'Length' . $i . '=' . $url->time . "\n";
        }

        $ret .= "Version=2\n";

        return $ret;
    } // get_pls_string

    public function create_pls()
    {
        echo $this->get_pls_string();
    }

    /**
     * get_asx_string
     * This should really only be used if all of the content is ASF files.
     */
    public function get_asx_string()
    {
        $ret = '<ASX VERSION="3.0" BANNERBAR="auto">' . "\n";
        $ret .= "<TITLE>" . ($this->title ?: "Ampache ASX Playlist") . "</TITLE>\n";
        $ret .= '<PARAM NAME="Encoding" VALUE="utf-8" />' . "\n";

        foreach ($this->urls as $url) {
            $ret .= "<ENTRY>\n";
            $ret .= '<TITLE>' . scrub_out($url->title) . "</TITLE>\n";
            $ret .= '<AUTHOR>' . scrub_out($url->author) . "</AUTHOR>\n";
            //FIXME: duration looks hacky and wrong
            $ret .= "\t\t" . '<DURATION VALUE="00:00:' . $url->time . '" />' . "\n";
            $ret .= "\t\t" . '<PARAM NAME="Album" Value="' . scrub_out($url->album) . '" />' . "\n";
            $ret .= "\t\t" . '<PARAM NAME="Composer" Value="' . scrub_out($url->author) . '" />' . "\n";
            $ret .= "\t\t" . '<PARAM NAME="Prebuffer" Value="false" />' . "\n";
            $ret .= '<REF HREF="' . $url->url . '" />' . "\n";
            $ret .= "</ENTRY>\n";
        }

        $ret .= "</ASX>\n";

        return $ret;
    } // get_asx_string

    public function create_asx()
    {
        echo $this->get_asx_string();
    }

    /**
     * get_xspf_string
     */
    public function get_xspf_string()
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
            if ($url->track_num) {
                $xml['track']['trackNum'] = $url->track_num;
            }

            $result .= XML_Data::keyed_array($xml, true);
        } // end foreach

        XML_Data::set_type('xspf');
        $ret = XML_Data::header($this->title);
        $ret .= $result;
        $ret .= XML_Data::footer();

        return $ret;
    } // get_xspf_string

    public function create_xspf()
    {
        echo $this->get_xspf_string();
    }

    public function get_hls_string()
    {
        $ssize = 10;
        $ret   = "#EXTM3U\n";
        $ret .= "#EXT-X-TARGETDURATION:" . $ssize . "\n";
        $ret .= "#EXT-X-VERSION:1\n";
        $ret .= "#EXT-X-ALLOW-CACHE:NO\n";
        $ret .= "#EXT-X-MEDIA-SEQUENCE:0\n";
        $ret .= "#EXT-X-PLAYLIST-TYPE:VOD\n";   // Static list of segments

        foreach ($this->urls as $url) {
            $soffset = 0;
            $segment = 0;
            while ($soffset < $url->time) {
                $type              = $url->type;
                $size              = (($soffset + $ssize) <= $url->time) ? $ssize : ($url->time - $soffset);
                $additional_params = '&transcode_to=ts&segment=' . $segment;
                $ret .= "#EXTINF:" . $size . ",\n";
                $purl = Stream_URL::parse($url->url);
                $id   = $purl['id'];

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
                $ret .= $hu . "\n";
                $soffset += $size;
                $segment++;
            }
        }

        $ret .= "#EXT-X-ENDLIST\n\n";

        return $ret;
    }
    
    public function create_hls()
    {
        echo $this->get_hls_string();
    }

    /**
     * create_web_player
     *
     * Creates an web player.
     */
    public function create_web_player()
    {
        if (AmpConfig::get("ajax_load")) {
            require AmpConfig::get('prefix') . UI::find_template('create_web_player_embedded.inc.php');
        } else {
            require AmpConfig::get('prefix') . UI::find_template('create_web_player.inc.php');
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
            // We don't have metadata on Stream_URL to know its kind
            // so we check the content to know if it is democratic
            if (count($this->urls) == 1) {
                $furl = $this->urls[0];
                if (strpos($furl->url, "&demo_id=1") !== false && $furl->time == -1) {
                    // If democratic, repeat the song to get the next voted one.
                    debug_event('stream_playlist', 'Playing democratic on localplay, enabling repeat...', 5);
                    $localplay->repeat(true);
                }
            }
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
            $data    = Stream_URL::parse($url->url);
            $items[] = array($data['type'], $data['id']);
        }

        $democratic->add_vote($items);
        display_notification(T_('Vote added'));
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
