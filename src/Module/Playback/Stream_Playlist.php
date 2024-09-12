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

namespace Ampache\Module\Playback;

use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Media;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Ui;
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\Model\Video;

/**
 * Stream_Playlist Class
 *
 * This class is used to generate the Playlists and pass them on
 * For Localplay this actually just sends the commands to the Localplay
 * module in question.
 */
class Stream_Playlist
{
    private const STREAM_PLAYLIST_ROW = [
        'sid' => null,
        'url' => "",
        'info_url' => null,
        'image_url' => null,
        'title' => null,
        'author' => null,
        'album' => null,
        'type' => null,
        'time' => null,
        'codec' => null,
        'track_num' => 0,
    ];

    private ?string $streamtoken = null;

    public string $id;

    /** @var list<Stream_Url> */
    public array $urls = [];

    public int $user;

    public ?string $title = null;

    /**
     * Stream_Playlist constructor
     * If an ID is passed, it should be a stream session ID.
     * @param int|string $session_id
     */
    public function __construct($session_id = null)
    {
        if ($session_id != -1) {
            if ($session_id !== null) {
                Stream::set_session($session_id);
            }

            $this->id = Stream::get_session();
            if (!Session::exists(AccessTypeEnum::STREAM->value, $this->id)) {
                debug_event(self::class, 'Session::exists failed', 2);

                return;
            }

            $user = Core::get_global('user');
            if (!$user instanceof User) {
                debug_event(self::class, 'No User found', 2);

                return;
            }
            $this->user        = $user->id;
            $this->streamtoken = $user->streamtoken;

            $sql        = 'SELECT * FROM `stream_playlist` WHERE `sid` = ? ORDER BY `id`';
            $db_results = Dba::read($sql, [$this->id]);

            while ($row = Dba::fetch_assoc($db_results)) {
                $this->urls[] = new Stream_Url($row);
            }
        }
    }

    private function _add_url(Stream_Url $url): void
    {
        debug_event(self::class, "Adding url {" . json_encode($url) . "}...", 5);

        $this->urls[] = $url;
        $fields       = [];
        $fields[]     = '`sid`';
        $values       = [];
        $values[]     = $this->id;
        $holders      = [];
        $holders[]    = '?';

        foreach ($url->properties as $field) {
            if ($url->$field) {
                $fields[]  = '`' . $field . '`';
                $values[]  = $url->$field;
                $holders[] = '?';
            }
        }
        $sql = 'INSERT INTO `stream_playlist` (' . implode(',', $fields) . ') VALUES (' . implode(',', $holders) . ')';

        Dba::write($sql, $values);
    }

    /**
     * @param list<Stream_Url> $urls
     */
    private function _add_urls(array $urls): void
    {
        debug_event(self::class, "Adding urls to {" . $this->id . "}...", 5);
        $sql         = '';
        $fields      = [];
        $values      = [];
        $holders_arr = [];

        foreach ($urls as $url) {
            $this->urls[] = $url;
            $fields       = [];
            $fields[]     = '`sid`';
            $values[]     = $this->id;
            $holders      = [];
            $holders[]    = '?';

            foreach ($url->properties as $field) {
                if ($url->$field !== null) {
                    $fields[]  = '`' . $field . '`';
                    $values[]  = $url->$field;
                    $holders[] = '?';
                }
            }
            $holders_arr[] = $holders;
        }

        $holders_chunks = array_chunk($holders_arr, 500);
        foreach ($holders_chunks as $holders_arr_temp) {
            $sql .= 'INSERT INTO `stream_playlist` (' . implode(',', $fields) . ') VALUES ';

            foreach ($holders_arr_temp as $placeholder) {
                $sql .= '(' . implode(',', $placeholder) . '),';
            }
            // remove last comma
            $sql = substr($sql, 0, -1);
            $sql .= ';';
        }

        Dba::write($sql, $values);
    }

    /**
     * garbage_collection
     */
    public static function garbage_collection(): void
    {
        $sql = 'DELETE FROM `stream_playlist` USING `stream_playlist` LEFT JOIN `session` ON `session`.`id`=`stream_playlist`.`sid` WHERE `session`.`id` IS NULL';
        Dba::write($sql);
    }

    /**
     * media_to_urlarray
     * Formats the URL and media information and adds it to the object
     * @param list<array{
     *  object_type: LibraryItemEnum,
     *  object_id: int,
     *  client?: string,
     *  action?: string,
     *  cache?: string,
     *  player?: string,
     *  format?: string,
     *  transcode_to?: string,
     *  custom_play_action?: string
     * }> $media
     * @return list<Stream_Url>
     */
    public static function media_to_urlarray(array $media, string $additional_params = ''): array
    {
        $urls = [];
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
     * @param array{
     *  object_type?: LibraryItemEnum,
     *  object_id?: int,
     *  client?: string,
     *  action?: string,
     *  cache?: string,
     *  player?: string,
     *  format?: string,
     *  transcode_to?: string,
     *  custom_play_action?: string
     * } $media
     */
    public static function media_to_url(
        array $media,
        string $additional_params = '',
        string $urltype = 'web',
        ?User $user = null
    ): ?Stream_Url {
        // @todo use LibraryItemLoader
        $type      = $media['object_type']->value ?? null;
        $object_id = $media['object_id'] ?? null;
        if ($type === null || $object_id === null) {
            return null;
        }
        $className = ObjectTypeToClassNameMapper::map($type);
        /** @var library_item $object */
        $object = new $className($object_id);
        if ($object->isNew()) {
            return null;
        }
        $object->format();

        if (array_key_exists('client', $media)) {
            $additional_params .= "&client=" . $media['client'];
        }
        if (array_key_exists('action', $media)) {
            $additional_params .= "&action=" . $media['action'];
        }
        if (array_key_exists('cache', $media)) {
            $additional_params .= "&cache=" . $media['cache'];
        }
        if (array_key_exists('player', $media)) {
            $additional_params .= "&player=" . $media['player'];
        }
        if (array_key_exists('format', $media)) {
            $additional_params .= "&format=" . $media['format'];
        }
        if (array_key_exists('transcode_to', $media)) {
            $additional_params .= "&transcode_to=" . $media['transcode_to'];
        }
        if (array_key_exists('custom_play_action', $media)) {
            $additional_params .= "&custom_play_action=" . $media['custom_play_action'];
        }

        if (array_key_exists('iframe', $_SESSION) && array_key_exists('subtitle', $_SESSION['iframe'])) {
            $additional_params .= "&subtitle=" . $_SESSION['iframe']['subtitle'];
        }

        if ($object instanceof Media) {
            return self::media_object_to_url($object, $additional_params, $urltype, $user);
        }

        return null;
    }

    private static function media_object_to_url(Media $object, string $additional_params = '', string $urltype = 'web', ?User $user = null): ?Stream_Url
    {
        $surl = null;
        $url  = self::STREAM_PLAYLIST_ROW;
        if (!$user) {
            $user = Core::get_global('user');
        }

        $type = $object->getMediaType();

        $url['type'] = $type->value;

        // Don't add disabled media objects to the stream playlist
        // Playing a disabled media return a 404 error that could make failed the player (mpd ...)
        if (!isset($object->enabled) || make_bool($object->enabled)) {
            if (
                $urltype == 'file' &&
                isset($object->file)
            ) {
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
            } elseif (in_array($type, [LibraryItemEnum::SONG, LibraryItemEnum::PODCAST_EPISODE, LibraryItemEnum::VIDEO])) {
                /** @var Song|Podcast_Episode|Video $object */
                $url['url'] = (!empty($user))
                    ? $object->play_url($additional_params, '', false, $user->id, $user->streamtoken)
                    : $object->play_url($additional_params);
            } else {
                $url['url'] = $object->play_url($additional_params);
            }

            $api_session = (AmpConfig::get('require_session')) ? Stream::get_session() : null;

            // Set a default which can be overridden
            $url['author'] = 'Ampache';
            $url['time']   = (isset($object->time)) ? $object->time : 0;
            switch ($type) {
                case LibraryItemEnum::SONG:
                    /** @var Song $object */
                    $url['title']     = $object->title;
                    $url['author']    = $object->get_artist_fullname();
                    $url['info_url']  = $object->get_f_link();
                    $show_song_art    = AmpConfig::get('show_song_art', false);
                    $has_art          = Art::has_db($object->id, 'song');
                    $art_object       = ($show_song_art && $has_art) ? $object->id : $object->album;
                    $art_type         = ($show_song_art && $has_art) ? 'song' : 'album';
                    $url['image_url'] = Art::url($art_object, $art_type, $api_session, (AmpConfig::get('ajax_load') ? 3 : 4));
                    //$url['album']     = $object->f_album_full;
                    $url['codec']     = $object->type;
                    $url['track_num'] = (string)$object->track;
                    break;
                case LibraryItemEnum::VIDEO:
                    /** @var Video $object */
                    $url['title']     = 'Video - ' . $object->title;
                    $url['author']    = $object->get_artist_fullname();
                    $url['info_url']  = $object->get_f_link();
                    $url['image_url'] = Art::url($object->id, 'video', $api_session, (AmpConfig::get('ajax_load') ? 3 : 4));
                    $url['codec']     = $object->type;
                    break;
                case LibraryItemEnum::LIVE_STREAM:
                    /** @var Live_Stream $object */
                    $url['title'] = 'Radio - ' . $object->name;
                    if (!empty($object->site_url)) {
                        $url['title'] .= ' (' . $object->site_url . ')';
                    }
                    $url['info_url']  = $object->get_f_link();
                    $url['image_url'] = Art::url($object->id, 'live_stream', $api_session, (AmpConfig::get('ajax_load') ? 3 : 4));
                    $url['codec']     = $object->codec;
                    break;
                case LibraryItemEnum::SONG_PREVIEW:
                    /** @var Song_Preview $object */
                    $url['title']  = $object->title;
                    $url['author'] = $object->get_artist_fullname();
                    $url['codec']  = $object->type;
                    break;
                case LibraryItemEnum::PODCAST_EPISODE:
                    /** @var Podcast_Episode $object */
                    $url['title']     = $object->f_name;
                    $url['author']    = $object->getPodcastName();
                    $url['info_url']  = $object->get_f_link();
                    $url['image_url'] = Art::url($object->podcast, 'podcast', $api_session, (AmpConfig::get('ajax_load') ? 3 : 4));
                    $url['codec']     = $object->type;
                    break;
                default:
                    $url['title'] = Stream_Url::get_title($url['url']);
                    $url['time']  = -1;
                    break;
            }

            $surl = new Stream_Url($url);
        }

        return $surl;
    }

    /**
     * check_autoplay_append
     */
    public static function check_autoplay_append(): bool
    {
        // For now, only iframed web player support media append in the currently played playlist
        return ((AmpConfig::get('ajax_load') && AmpConfig::get('play_type') == 'web_player') || AmpConfig::get('play_type') == 'localplay');
    }

    /**
     * check_autoplay_next
     */
    public static function check_autoplay_next(): bool
    {
        // Currently only supported for web player
        return (AmpConfig::get('ajax_load') && AmpConfig::get('play_type') == 'web_player');
    }

    public function generate_playlist(string $type, bool $redirect = false, ?string $name = ''): bool
    {
        if (!count($this->urls)) {
            debug_event(self::class, 'Error: Empty URL array for ' . $this->id, 2);

            return false;
        }

        debug_event(self::class, 'Generating a {' . $type . '} object...', 4);

        $ext = $type;
        switch ($type) {
            case 'download':
                $ctype    = "";
                $redirect = false;
                unset($ext);
                $callable = function (): void {
                    $this->create_download();
                };
                break;
            case 'democratic':
                $ctype    = "";
                $redirect = false;
                unset($ext);
                $callable = function (): void {
                    $this->create_democratic();
                };
                break;
            case 'localplay':
                $ctype    = "";
                $redirect = false;
                unset($ext);
                $callable = function (): void {
                    $this->create_localplay();
                };
                break;
            case 'web_player':
                // These are valid, but witchy
                $ctype    = "";
                $redirect = false;
                unset($ext);
                $callable = function (): void {
                    $this->create_web_player();
                };
                break;
            case 'asx':
                $ctype    = 'video/x-ms-asf';
                $callable = function (): void {
                    $this->create_asx();
                };
                break;
            case 'pls':
                $ctype    = 'audio/x-scpls';
                $callable = function (): void {
                    $this->create_pls();
                };
                break;
            case 'ram':
                $ctype    = 'audio/x-pn-realaudio ram';
                $callable = function (): void {
                    $this->create_ram();
                };
                break;
            case 'simple_m3u':
                $ext      = 'm3u';
                $ctype    = 'audio/x-mpegurl';
                $callable = function (): void {
                    $this->create_simple_m3u();
                };
                break;
            case 'xspf':
                $ctype    = 'application/xspf+xml';
                $callable = function (): void {
                    $this->create_xspf();
                };
                break;
            case 'hls':
                $ext      = 'm3u8';
                $ctype    = 'application/vnd.apple.mpegurl';
                $callable = function (): void {
                    $this->create_hls();
                };
                break;
            case 'm3u':
            default:
                // Assume M3U if the pooch is screwed
                $ext      = $type = 'm3u';
                $ctype    = 'audio/x-mpegurl';
                $callable = function (): void {
                    echo $this->create_m3u();
                };
                break;
        }

        if ($redirect) {
            // Our ID is the SID, so we always want to include it
            AmpConfig::set('require_session', true, true);
            header('Location: ' . Stream::get_base_url(false, $this->streamtoken) . 'uid=' . $this->user . '&type=playlist&playlist_type=' . scrub_out($type));

            return false;
        }
        $filename = (!empty($name)) ? rawurlencode($name) : 'ampache_playlist';
        if (isset($ext)) {
            header('Cache-control: public');
            header('Content-Disposition: filename=' . $filename . '.' . $ext);
            header('Content-Type: ' . $ctype . ';');
        }

        $callable();

        return true;
    }

    /**
     * add
     * Adds an array of media
     * @param list<array{
     *  object_type: LibraryItemEnum,
     *  object_id: int,
     *  client?: string,
     *  action?: string,
     *  cache?: string,
     *  player?: string,
     *  format?: string,
     *  transcode_to?: string,
     *  custom_play_action?: string
     * }> $media
     * @param string $additional_params
     */
    public function add(array $media = [], string $additional_params = ''): void
    {
        $urls = self::media_to_urlarray($media, $additional_params);
        $this->_add_urls($urls);
    }

    /**
     * add_urls
     * Add an array of urls. This is used for things that aren't coming
     * from media objects like democratic playlists
     * @param list<string> $urls
     */
    public function add_urls(array $urls = []): bool
    {
        foreach ($urls as $url) {
            $this->_add_url(
                new Stream_Url(
                    [
                        'url' => $url,
                        'title' => Stream_Url::get_title($url),
                        'author' => T_('Ampache'),
                        'time' => '-1'
                    ]
                )
            );
        }

        return true;
    }

    /**
     * create_simplem3u
     * this creates a simple m3u without any of the extended information
     */
    public function create_simple_m3u(): void
    {
        foreach ($this->urls as $url) {
            echo $url->url . "\n";
        }
    }

    /**
     * for compatibility, the get_m3u_string function name is generated in the ExportPlaylist console export
     */
    public function get_m3u_string(): string
    {
        return $this->create_m3u();
    }

    /**
     * creates the content of an m3u file, this includes the EXTINFO and as such can be
     * large with very long playlists
     */
    public function create_m3u(): string
    {
        $ret = "#EXTM3U\n";

        foreach ($this->urls as $url) {
            $ret .= '#EXTINF:' . $url->time . ', ' . $url->author . ' - ' . $url->title . "\n";
            $ret .= $url->url . "\n";
        }

        return $ret;
    }

    public function create_pls(): void
    {
        $ret = "[playlist]\n";
        $ret .= 'NumberOfEntries=' . count($this->urls) . "\n";
        $count = 0;
        foreach ($this->urls as $url) {
            $count++;
            $ret .= 'File' . $count . '=' . $url->url . "\n";
            $ret .= 'Title' . $count . '=' . $url->author . ' - ' . $url->title . "\n";
            $ret .= 'Length' . $count . '=' . $url->time . "\n";
        }

        $ret .= "Version=2\n";

        echo $ret;
    }

    /**
     * This should really only be used if all of the content is ASF files.
     */
    public function create_asx(): void
    {
        $ret = '<ASX VERSION="3.0" BANNERBAR="auto">' . "\n";
        $ret .= "<TITLE>" . ($this->title ?? T_("Ampache ASX Playlist")) . "</TITLE>\n";
        $ret .= '<PARAM NAME="Encoding" VALUE="utf-8"' . "></PARAM>\n";

        foreach ($this->urls as $url) {
            $ret .= "<ENTRY>\n";
            $ret .= '<TITLE>' . scrub_out($url->title) . "</TITLE>\n";
            $ret .= '<AUTHOR>' . scrub_out($url->author) . "</AUTHOR>\n";
            // FIXME: duration looks hacky and wrong
            $ret .= "\t\t" . '<DURATION VALUE="00:00:' . $url->time . '" />' . "\n";
            $ret .= "\t\t" . '<PARAM NAME="Album" Value="' . scrub_out($url->album) . '" />' . "\n";
            $ret .= "\t\t" . '<PARAM NAME="Composer" Value="' . scrub_out($url->author) . '" />' . "\n";
            $ret .= "\t\t" . '<PARAM NAME="Prebuffer" Value="false" />' . "\n";
            $ret .= '<REF HREF="' . $url->url . '" />' . "\n";
            $ret .= "</ENTRY>\n";
        }

        $ret .= "</ASX>\n";

        echo $ret;
    }

    public function create_xspf(): void
    {
        $result = "";
        foreach ($this->urls as $url) {
            $xml = [];

            $xml['track'] = [
                'title' => $url->title,
                'creator' => $url->author,
                'duration' => (int) $url->time * 1000,
                'location' => $url->url,
                'identifier' => $url->url
            ];
            if ($url->type == 'video') {
                $xml['track']['meta'] = [
                    'attribute' => 'rel="provider"',
                    'value' => 'video'
                ];
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

            $result .= Xml_Data::keyed_array($xml, true);
        } // end foreach

        $ret = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<playlist version = \"1\" xmlns=\"http://xspf.org/ns/0/\">\n<title>" . $this->title . "</title>\n<creator>" . scrub_out(AmpConfig::get('site_title')) . "</creator>\n<annotation>" . scrub_out(AmpConfig::get('site_title')) . "</annotation>\n<info>" . AmpConfig::get_web_path('/client') . "</info>\n<trackList>\n";
        $ret .= $result;
        $ret .= "</trackList>\n</playlist>\n";

        echo $ret;
    }

    public function create_hls(): void
    {
        $ssize = 10;
        $ret   = "#EXTM3U\n";
        $ret .= "#EXT-X-TARGETDURATION:" . $ssize . "\n";
        $ret .= "#EXT-X-VERSION:1\n";
        $ret .= "#EXT-X-ALLOW-CACHE:NO\n";
        $ret .= "#EXT-X-MEDIA-SEQUENCE:0\n";
        $ret .= "#EXT-X-PLAYLIST-TYPE:VOD\n"; // Static list of segments

        foreach ($this->urls as $url) {
            $soffset = 0;
            $segment = 0;
            while ($soffset < $url->time) {
                $type              = $url->type;
                $size              = (($soffset + $ssize) <= $url->time) ? $ssize : ((int) $url->time - $soffset);
                $additional_params = '&transcode_to=ts&segment=' . $segment;
                $ret .= "#EXTINF:" . $size . ",\n";
                $url_data = Stream_Url::parse($url->url);
                $url_id   = $url_data['id'];

                unset($url_data['id']);
                unset($url_data['ssid']);
                unset($url_data['type']);
                unset($url_data['base_url']);
                unset($url_data['uid']);
                unset($url_data['name']);

                foreach ($url_data as $key => $value) {
                    $additional_params .= '&' . $key . '=' . $value;
                }

                $className = ObjectTypeToClassNameMapper::map($type);
                /** @var Media $item */
                $item = new $className($url_id);
                $hu   = $item->play_url($additional_params);
                $ret .= $hu . "\n";
                $soffset += $size;
                $segment++;
            }
        }

        $ret .= "#EXT-X-ENDLIST\n\n";

        echo $ret;
    }

    /**
     * create_web_player
     *
     * Creates an web player.
     */
    public function create_web_player(): void
    {
        if (AmpConfig::get("ajax_load")) {
            require Ui::find_template('create_web_player_embedded.inc.php');
        } else {
            require Ui::find_template('create_web_player.inc.php');
        }
    }

    /**
     * create_localplay
     * This calls the Localplay API to add the URLs and then start playback
     */
    public function create_localplay(): void
    {
        $localplay = new LocalPlay(AmpConfig::get('localplay_controller', ''));
        $localplay->connect();
        $append = $_REQUEST['append'] ?? false;
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
                    debug_event(self::class, 'Playing democratic on Localplay, enabling repeat...', 5);
                    $localplay->repeat(true);
                }
            }
            $localplay->play();
        }
    }

    /**
     * create_democratic
     *
     * This 'votes' on the songs; it inserts them into a tmp_playlist with user
     * set to -1.
     */
    public function create_democratic(): void
    {
        $democratic = Democratic::get_current_playlist();
        $democratic->set_parent();
        $items = [];

        foreach ($this->urls as $url) {
            $url_data = Stream_Url::parse($url->url);
            $items[]  = [
                $url_data['type'],
                $url_data['id']
            ];
        }
        if (!empty($items)) {
            $democratic->add_vote($items);
            display_notification(T_('Vote added'));
        }
    }

    /**
     * create_download
     * This prompts for a download of the song
     */
    public function create_download(): bool
    {
        // There should only be one here...
        if (count($this->urls) != 1) {
            debug_event(self::class, 'Download called, but $urls contains ' . json_encode($this->urls), 2);
        }

        // Header redirect baby!
        $url = current($this->urls);
        if ($url === false) {
            return false;
        }

        $url = Stream_Url::add_options($url->url, '&action=download&cache=1');
        header('Location: ' . $url);

        return false;
    }

    /**
     * create_ram
     * this functions creates a RAM file for use by Real Player
     */
    public function create_ram(): void
    {
        foreach ($this->urls as $url) {
            echo $url->url . "\n";
        }
    }
}
