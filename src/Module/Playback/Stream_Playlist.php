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

namespace Ampache\Module\Playback;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\MediaUrlListGenerator\MediaUrlListGeneratorInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Media;
use PDOStatement;
use Psr\Http\Message\ResponseInterface;

/**
 * Stream_Playlist Class
 *
 * This class is used to generate the Playlists and pass them on
 * For Localplay this actually just sends the commands to the Localplay
 * module in question.
 */
class Stream_Playlist
{
    public $id;

    /** @var Stream_Url[] $urls */
    public $urls = array();
    public $user;

    public $title;

    /**
     * Stream_Playlist constructor
     * If an ID is passed, it should be a stream session ID.
     * @param integer|string $session_id
     */
    public function __construct($session_id = null)
    {
        if ($session_id != -1) {
            if ($session_id !== null) {
                Stream::set_session($session_id);
            }

            $this->id = Stream::get_session();

            if (!Session::exists('stream', $this->id)) {
                debug_event(self::class, 'Session::exists failed', 2);

                return false;
            }

            $this->user = (int)(Core::get_global('user')->id);

            $sql        = 'SELECT * FROM `stream_playlist` WHERE `sid` = ? ORDER BY `id`';
            $db_results = Dba::read($sql, array($this->id));

            while ($row = Dba::fetch_assoc($db_results)) {
                $this->urls[] = new Stream_Url($row);
            }
        }

        return true;
    }

    /**
     * @param Stream_URL $url
     * @return PDOStatement|boolean
     */
    private function _add_url($url)
    {
        debug_event("stream_playlist.class", "Adding url {" . json_encode($url) . "}...", 5);

        $this->urls[] = $url;
        $fields       = array();
        $fields[]     = '`sid`';
        $values       = array();
        $values[]     = $this->id;
        $holders      = array();
        $holders[]    = '?';

        foreach ($url->properties as $field) {
            if ($url->$field) {
                $fields[]  = '`' . $field . '`';
                $values[]  = $url->$field;
                $holders[] = '?';
            }
        }
        $sql = 'INSERT INTO `stream_playlist` (' . implode(',', $fields) . ') VALUES (' . implode(',', $holders) . ')';

        return Dba::write($sql, $values);
    }

    /**
     * @param array $urls
     * @return PDOStatement|boolean
     */
    private function _add_urls($urls)
    {
        $sql       = 'INSERT INTO `stream_playlist` ';
        $value_sql = 'VALUES ';
        $values    = array();
        $fields    = array();
        $fields[]  = '`sid`';
        $count     = true;
        debug_event("stream_playlist.class", "Adding urls to {" . $this->id . "}...", 5);
        foreach ($urls as $url) {
            $this->urls[] = $url;
            $values[]     = $this->id;
            $holders      = array();
            $holders[]    = '?';

            foreach ($url->properties as $field) {
                if ($url->$field) {
                    $holders[] = '?';
                    $values[]  = $url->$field;
                    if ($count) {
                        $fields[] = '`' . $field . '`';
                    }
                }
            }
            $count = false;
            $value_sql .= '(' . implode(',', $holders) . '), ';
        }
        $sql .= '(' . implode(',', $fields) . ') ';

        return Dba::write($sql . rtrim($value_sql, ', '), $values);
    }

    /**
     * @return PDOStatement|boolean
     */
    public static function garbage_collection()
    {
        $sql = 'DELETE FROM `stream_playlist` USING `stream_playlist` ' . 'LEFT JOIN `session` ON `session`.`id`=`stream_playlist`.`sid` ' . 'WHERE `session`.`id` IS NULL';

        return Dba::write($sql);
    }

    /**
     * media_to_urlarray
     * Formats the URL and media information and adds it to the object
     * @param $media
     * @param string $additional_params
     * @return array
     */
    public static function media_to_urlarray($media, $additional_params = '')
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
     * @param $media
     * @param string $additional_params
     * @param string $urltype
     * @return Stream_Url
     */
    public static function media_to_url($media, $additional_params = '', $urltype = 'web')
    {
        $type       = $media['object_type'];
        $object_id  = $media['object_id'];
        $class_name = ObjectTypeToClassNameMapper::map($type);
        $object     = new $class_name($object_id);
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
     * @param media $object
     * @param string $additional_params
     * @param string $urltype
     * @return Stream_Url
     */
    public static function media_object_to_url($object, $additional_params = '', $urltype = 'web')
    {
        $surl = null;
        $url  = array();

        $class_name  = get_class($object);
        $type        = ObjectTypeToClassNameMapper::reverseMap($class_name);
        $url['type'] = $type;

        // Don't add disabled media objects to the stream playlist
        // Playing a disabled media return a 404 error that could make failed the player (mpd ...)
        if (!isset($object->enabled) || make_bool($object->enabled)) {
            if ($urltype == 'file') {
                $url['url'] = $object->file;
                // Relative path
                if (!empty($additional_params) && strpos($url['url'], $additional_params) === 0) {
                    $url['url'] = substr($url['url'], strlen((string)$additional_params));
                    if (strlen((string)$url['url']) < 1) {
                        return null;
                    }
                    if ($url['url'][0] == DIRECTORY_SEPARATOR) {
                        $url['url'] = substr($url['url'], 1);
                    }
                }
            } else {
                $url['url'] = $object->play_url($additional_params);
            }

            $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : null;

            // Set a default which can be overridden
            $url['author'] = 'Ampache';
            $url['time']   = $object->time;
            switch ($type) {
                case 'song':
                    $url['title']     = $object->title;
                    $url['author']    = $object->f_artist_full;
                    $url['info_url']  = $object->f_link;
                    $show_song_art    = AmpConfig::get('show_song_art', false);
                    $art_object       = ($show_song_art) ? $object->id : $object->album;
                    $art_type         = ($show_song_art) ? 'song' : 'album';
                    $url['image_url'] = Art::url($art_object, $art_type, $api_session, (AmpConfig::get('ajax_load') ? 3 : 4));
                    $url['album']     = $object->f_album_full;
                    $url['codec']     = $object->type;
                    //$url['track_num'] = $object->f_track;
                    break;
                case 'video':
                    $url['title']      = 'Video - ' . $object->title;
                    $url['author']     = $object->f_artist_full;
                    $url['resolution'] = $object->f_resolution;
                    $url['codec']      = $object->type;
                    break;
                case 'live_stream':
                    $url['title'] = 'Radio - ' . $object->name;
                    if (!empty($object->site_url)) {
                        $url['title'] .= ' (' . $object->site_url . ')';
                    }
                    $url['image_url'] = Art::url($object->id, 'live_stream', $api_session, (AmpConfig::get('ajax_load') ? 3 : 4));
                    $url['codec']     = $object->codec;
                    break;
                case 'song_preview':
                    $url['title']  = $object->title;
                    $url['author'] = $object->f_artist_full;
                    $url['codec']  = $object->type;
                    break;
                case 'channel':
                    $url['title'] = $object->name;
                    $url['codec'] = $object->stream_type;
                    break;
                case 'podcast_episode':
                    $url['title']     = $object->f_title;
                    $url['author']    = $object->f_podcast;
                    $url['info_url']  = $object->f_link;
                    $url['image_url'] = Art::url($object->podcast, 'podcast', $api_session, (AmpConfig::get('ajax_load') ? 3 : 4));
                    $url['codec']     = $object->type;
                    break;
                case 'random':
                    $url['title'] = 'Random URL';
                    break;
                default:
                    $url['title'] = 'URL-Add';
                    $url['time']  = -1;
                    break;
            }

            $surl = new Stream_Url($url);
        }

        return $surl;
    }

    /**
     * check_autoplay_append
     * @return boolean
     */
    public static function check_autoplay_append()
    {
        // For now, only iframed web player support media append in the currently played playlist
        return ((AmpConfig::get('ajax_load') && AmpConfig::get('play_type') == 'web_player') || AmpConfig::get('play_type') == 'localplay');
    }

    /**
     * check_autoplay_next
     * @return boolean
     */
    public static function check_autoplay_next()
    {
        // Currently only supported for web player
        return (AmpConfig::get('ajax_load') && AmpConfig::get('play_type') == 'web_player');
    }

    /**
     * @param $type
     */
    public function generate_playlist($type): ResponseInterface
    {
        return $this->getMediaUrlListGenerator()->generate($this, $type);
    }

    /**
     * add
     * Adds an array of media
     * @param array $media
     * @param string $additional_params
     */
    public function add($media = array(), $additional_params = '')
    {
        $urls = self::media_to_urlarray($media, $additional_params);
        $this->_add_urls($urls);
    }

    /**
     * add_urls
     * Add an array of urls. This is used for things that aren't coming
     * from media objects like democratic playlists
     * @param array $urls
     * @return boolean
     */
    public function add_urls($urls = array())
    {
        if (!is_array($urls)) {
            return false;
        }

        foreach ($urls as $url) {
            $this->_add_url(new Stream_Url(array(
                'url' => $url,
                'title' => 'URL-Add',
                'author' => 'Ampache',
                'time' => '-1'
            )));
        }

        return true;
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getMediaUrlListGenerator(): MediaUrlListGeneratorInterface
    {
        global $dic;

        return $dic->get(MediaUrlListGeneratorInterface::class);
    }
}
