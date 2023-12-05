<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Repository\Model;

use Ampache\Module\Api\Ajax;
use Ampache\Config\AmpConfig;
use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;
use Ampache\Repository\ShoutRepositoryInterface;

/**
 * Browse Class
 *
 * This handles all of the sql/filtering
 * on the data before it's thrown out to the templates
 * it also handles pulling back the object_ids and then
 * calling the correct template for the object we are displaying
 *
 */
class Browse extends Query
{
    private const BROWSE_TYPES = array(
        'album',
        'album_disk',
        'artist',
        'broadcast',
        'catalog',
        'clip',
        'democratic',
        'label',
        'license',
        'live_stream',
        'movie',
        'personal_video',
        'playlist',
        'playlist_localplay',
        'playlist_media',
        'podcast',
        'podcast_episode',
        'pvmsg',
        'share',
        'shoutbox',
        'smartplaylist',
        'song',
        'song_preview',
        'tag',
        'tag_hidden',
        'tvshow',
        'tvshow_episode',
        'tvshow_season',
        'user',
        'video',
        'wanted'
    );

    /**
     * @var int $duration
     */
    public $duration;

    /**
     * Constructor.
     *
     * @param int|null $browse_id
     * @param bool $cached
     */
    public function __construct($browse_id = 0, $cached = true)
    {
        parent::__construct($browse_id, $cached);

        if (!$browse_id) {
            $this->set_use_pages(true);
            $this->set_use_alpha(false);
            $this->set_grid_view(true);
        }
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * set_simple_browse
     * This sets the current browse object to a 'simple' browse method
     * which means use the base query provided and expand from there
     */
    public function set_simple_browse(bool $value): void
    {
        $this->set_is_simple($value);
    }

    /**
     * is_valid_type
     * Validate the browse is a type of object you can actually browse
     */
    public function is_valid_type(string $type): bool
    {
        return in_array($type, self::BROWSE_TYPES);
    }

    /**
     * add_supplemental_object
     * Legacy function, need to find a better way to do that
     */
    public function add_supplemental_object(string $class, int $uid): bool
    {
        $_SESSION['browse']['supplemental'][$this->id][$class] = $uid;

        return true;
    }

    /**
     * get_supplemental_objects
     * This returns an array of 'class', 'id' for additional objects that
     * need to be created before we start this whole browsing thing.
     */
    public function get_supplemental_objects(): array
    {
        $objects = $_SESSION['browse']['supplemental'][$this->id] ?? '';

        if (!is_array($objects)) {
            $objects = array();
        }

        return $objects;
    }

    /**
     * update_browse_from_session
     * Restore the previous start index from something saved into the current session.
     */
    public function update_browse_from_session(): void
    {
        if ($this->is_simple() && $this->get_start() == 0) {
            $name = 'browse_current_' . $this->get_type();
            if (array_key_exists($name, $_SESSION) && array_key_exists('start', $_SESSION[$name]) && $_SESSION[$name]['start'] > 0) {
                // Checking if value is suitable
                $start = (int)($_SESSION[$name]['start'] ?? 0);
                if ($this->get_offset() > 0) {
                    $set_page = floor($start / $this->get_offset());
                    if ($this->get_total() > $this->get_offset()) {
                        $total_pages = ceil($this->get_total() / $this->get_offset());
                    } else {
                        $total_pages = 0;
                    }

                    if ($set_page >= 0 && $set_page <= $total_pages) {
                        $this->set_start($start);
                    }
                }
            }
        }
    }

    /**
     * show_objects
     * This takes an array of objects
     * and requires the correct template based on the
     * type that we are currently browsing
     *
     * @param array $object_ids
     * @param bool|array|string $argument
     */
    public function show_objects($object_ids = array(), $argument = false): void
    {
        if ($this->is_simple() || !is_array($object_ids) || empty($object_ids)) {
            $object_ids = $this->get_saved();
        } else {
            $this->save_objects($object_ids);
        }

        // Limit is based on the user's preferences if this is not a
        // simple browse because we've got too much here
        if ($this->get_start() >= 0 && (count($object_ids) > $this->get_start()) && !$this->is_simple()) {
            $object_ids = array_slice($object_ids, $this->get_start(), $this->get_offset(), true);
        } elseif (!count($object_ids)) {
            $this->set_total(0);
        }

        // Load any additional object we need for this
        $extra_objects = $this->get_supplemental_objects();
        $browse        = $this;

        foreach ($extra_objects as $type => $extra_id) {
            $className = ObjectTypeToClassNameMapper::map($type);
            ${$type}   = new $className($extra_id);
        }

        $match = '';
        // Format any matches we have so we can show them to the masses
        if ($filter_value = $this->get_filter('alpha_match')) {
            $match = ' (' . (string)$filter_value . ')';
        } elseif ($filter_value = $this->get_filter('starts_with')) {
            $match = ' (' . (string)$filter_value . ')';
        } elseif ($filter_value = $this->get_filter('catalog')) {
            // Get the catalog title
            $catalog = Catalog::create_from_id((int)((string)$filter_value));
            if ($catalog !== null) {
                $match = ' (' . $catalog->name . ')';
            }
        }

        $type = $this->get_type();

        // Update the session value only if it's allowed on the current browser
        if ($this->is_update_session()) {
            $_SESSION['browse_current_' . $type]['start'] = $browse->get_start();
        }

        // Set the correct classes based on type
        $class = "box browse_" . $type . '_' . $this->id;
        debug_event(self::class, 'show_objects called. browse {' . $this->id . '} type {' . $type . '}', 5);

        // hide some of the useless columns in a browse
        $hide_columns   = array();
        $argument_param = '';
        if (is_array($argument)) {
            if (array_key_exists('hide', $argument) && is_array($argument['hide'])) {
                $hide_columns = $argument['hide'];
            }
            if (!empty($hide_columns)) {
                $argument_param = '&hide=';
                foreach ($hide_columns as $column) {
                    $argument_param .= scrub_in((string)$column) . ',';
                }
                $argument_param = rtrim($argument_param, ',');
            }
        } else {
            $argument_param = ($argument)
                ? '&argument=' . scrub_in((string)$argument)
                : '';
        }

        $box_title       = $this->get_title('');
        $limit_threshold = $this->get_threshold();
        // Switch on the type of browsing we're doing
        switch ($type) {
            case 'song':
                $box_title = $this->get_title(T_('Songs') . $match);
                Song::build_cache($object_ids, $limit_threshold);
                $box_req = Ui::find_template('show_songs.inc.php');
                break;
            case 'album':
                Album::build_cache($object_ids);
                $box_title     = $this->get_title(T_('Albums') . $match);
                $group_release = false;
                if (is_array($argument)) {
                    if (array_key_exists('title', $argument)) {
                        $box_title = $argument['title'];
                    }
                    if (array_key_exists('group_disks', $argument)) {
                        $group_release = (bool)$argument['group_disks'];
                    }
                }
                $box_req = Ui::find_template('show_albums.inc.php');
                break;
            case 'album_disk':
                $box_title     = $this->get_title(T_('Albums') . $match);
                $group_release = false;
                if (is_array($argument)) {
                    if (array_key_exists('title', $argument)) {
                        $box_title = $argument['title'];
                    }
                    if (array_key_exists('group_disks', $argument)) {
                        $group_release = (bool)$argument['group_disks'];
                    }
                }
                $box_req = Ui::find_template('show_album_disks.inc.php');
                break;
            case 'user':
                $box_title = $this->get_title(T_('Browse Users') . $match);
                $box_req   = Ui::find_template('show_users.inc.php');
                break;
            case 'artist':
                if ($this->is_album_artist()) {
                    $box_title = $this->get_title(T_('Album Artist') . $match);
                } elseif ($this->is_song_artist()) {
                    $box_title = $this->get_title(T_('Song Artist') . $match);
                } else {
                    $box_title = $this->get_title(T_('Artist') . $match);
                }
                Artist::build_cache($object_ids, true, $limit_threshold);
                $box_req = Ui::find_template('show_artists.inc.php');
                break;
            case 'live_stream':
                $box_title = $this->get_title(T_('Radio Stations') . $match);
                $box_req   = Ui::find_template('show_live_streams.inc.php');
                break;
            case 'playlist':
                Playlist::build_cache($object_ids);
                $box_title = $this->get_title(T_('Playlists') . $match);
                $box_req   = Ui::find_template('show_playlists.inc.php');
                break;
            case 'playlist_media':
                $box_title = $this->get_title(T_('Playlist Items') . $match);
                $box_req   = Ui::find_template('show_playlist_medias.inc.php');
                break;
            case 'playlist_localplay':
                $box_title = $this->get_title(T_('Current Playlist'));
                $box_req   = Ui::find_template('show_localplay_playlist.inc.php');
                Ui::show_box_bottom();
                break;
            case 'smartplaylist':
                $box_title = $this->get_title(T_('Smart Playlists') . $match);
                $box_req   = Ui::find_template('show_searches.inc.php');
                break;
            case 'catalog':
                $box_title = $this->get_title(T_('Catalogs'));
                $box_req   = Ui::find_template('show_catalogs.inc.php');
                break;
            case 'shoutbox':
                $shoutObjectLoader = $this->getShoutObjectLoader();
                $shoutRepository   = $this->getShoutRepository();
                $box_title         = $this->get_title(T_('Shoutbox Records'));
                $shouts            = [];
                foreach ($object_ids as $shoutId) {
                    $shout = $shoutRepository->findById($shoutId);
                    if ($shout !== null) {
                        // used within the template
                        $shouts[] = $shout;
                    }
                }
                $box_req   = Ui::find_template('show_manage_shoutbox.inc.php');
                break;
            case 'tag':
                Tag::build_cache($object_ids);
                $box_title = $this->get_title(T_('Genres'));
                $box_req   = Ui::find_template('show_tagcloud.inc.php');
                break;
            case 'tag_hidden':
                Tag::build_cache($object_ids);
                $box_title = $this->get_title(T_('Genres'));
                $box_req   = Ui::find_template('show_tagcloud_hidden.inc.php');
                break;
            case 'video':
                Video::build_cache($object_ids);
                $video_type = 'video';
                $box_title  = $this->get_title(T_('Videos'));
                $box_req    = Ui::find_template('show_videos.inc.php');
                break;
            case 'democratic':
                $box_title = $this->get_title(T_('Democratic Playlist'));
                $box_req   = Ui::find_template('show_democratic_playlist.inc.php');
                break;
            case 'wanted':
                $box_title = $this->get_title(T_('Wanted Albums'));
                $box_req   = Ui::find_template('show_wanted_albums.inc.php');
                break;
            case 'share':
                $box_title = $this->get_title(T_('Shares'));
                $box_req   = Ui::find_template('show_shared_objects.inc.php');
                break;
            case 'song_preview':
                $box_title = $this->get_title(T_('Songs'));
                $box_req   = Ui::find_template('show_song_previews.inc.php');
                break;
            case 'broadcast':
                $box_title = $this->get_title(T_('Broadcasts'));
                $box_req   = Ui::find_template('show_broadcasts.inc.php');
                break;
            case 'license':
                $box_title = $this->get_title(T_('Media Licenses'));
                $box_req   = Ui::find_template('show_manage_license.inc.php');
                break;
            case 'tvshow':
                $box_title = $this->get_title(T_('TV Shows'));
                $box_req   = Ui::find_template('show_tvshows.inc.php');
                break;
            case 'tvshow_season':
                $box_title = $this->get_title(T_('Seasons'));
                $box_req   = Ui::find_template('show_tvshow_seasons.inc.php');
                break;
            case 'tvshow_episode':
                $box_title  = $this->get_title(T_('Episodes'));
                $video_type = $type;
                $box_req    = Ui::find_template('show_videos.inc.php');
                break;
            case 'movie':
                $box_title  = $this->get_title(T_('Movies'));
                $video_type = $type;
                $box_req    = Ui::find_template('show_videos.inc.php');
                break;
            case 'clip':
                $box_title  = $this->get_title(T_('Clips'));
                $video_type = $type;
                $box_req    = Ui::find_template('show_videos.inc.php');
                break;
            case 'personal_video':
                $box_title  = $this->get_title(T_('Personal Videos'));
                $video_type = $type;
                $box_req    = Ui::find_template('show_videos.inc.php');
                break;
            case 'label':
                $box_title = $this->get_title(T_('Labels'));
                $box_req   = Ui::find_template('show_labels.inc.php');
                break;
            case 'pvmsg':
                $box_title = $this->get_title(T_('Private Messages'));
                $box_req   = Ui::find_template('show_pvmsgs.inc.php');
                break;
            case 'podcast':
                $box_title = $this->get_title(T_('Podcasts'));
                $box_req   = Ui::find_template('show_podcasts.inc.php');
                break;
            case 'podcast_episode':
                $box_title = $this->get_title(T_('Podcast Episodes'));
                $box_req   = Ui::find_template('show_podcast_episodes.inc.php');
                break;
        } // end switch on type

        Ajax::start_container($this->get_content_div(), 'browse_content');
        if ($this->is_show_header()) {
            if (isset($box_req) && !empty($box_title)) {
                $this->set_title($box_title);
                Ui::show_box_top($box_title, $class);
            }
        }

        if (isset($box_req)) {
            require $box_req;
        }

        if ($this->is_show_header()) {
            if (isset($box_req)) {
                Ui::show_box_bottom();
            }
            if ($this->is_use_filters()) {
                echo '<script>';
                echo Ajax::action('?page=browse&action=get_filters&browse_id=' . $this->id . $argument_param, '');
                echo ';</script>';
            }
        } elseif (!$this->is_use_pages()) {
            $this->show_next_link($argument);
        }
        // hide the filter box on some pages
        if (!$this->is_use_filters()) {
            echo '<script>';
            echo Ajax::action('?page=browse&action=hide_filters', '');
            echo ';</script>';
        }
        Ajax::end_container();
    }

    /**
     * @param $argument
     */
    public function show_next_link($argument = null): void
    {
        // FIXME Can be removed if Browse gets instantiated by the factory
        global $dic;

        $limit       = $this->get_offset();
        $start       = $this->get_start();
        $total       = $this->get_total();
        $next_offset = $start + $limit;
        if ($next_offset <= $total) {
            echo '<a class="jscroll-next" href="' . $dic->get(AjaxUriRetrieverInterface::class)->getAjaxUri() . '?page=browse&action=page&browse_id=' . $this->id . '&start=' . $next_offset . '&xoutput=raw&xoutputnode=' . $this->get_content_div() . '&show_header=false' . $argument . '">' . T_('More') . '</a>';
        }
    }

    /**
     * This sets the type of object that we want to browse by
     * @param string $type
     * @param string $custom_base
     */
    public function set_type($type, $custom_base = ''): void
    {
        if ($type === 'album_artist') {
            $this->set_type('artist', $custom_base);
            $this->set_album_artist(true);

            return;
        }
        if ($type === 'song_artist') {
            $this->set_type('artist', $custom_base);
            $this->set_song_artist(true);

            return;
        }
        if (self::is_valid_type($type)) {
            $name = 'browse_' . $type . '_pages';
            if ((isset($_COOKIE[$name]))) {
                $this->set_use_pages(Core::get_cookie($name) == 'true');
            }
            $name = 'browse_' . $type . '_alpha';
            if ((isset($_COOKIE[$name]))) {
                $this->set_use_alpha(Core::get_cookie($name) == 'true');
            } else {
                $default_alpha = (!AmpConfig::get('libitem_browse_alpha')) ? array() : explode(
                    ",",
                    AmpConfig::get('libitem_browse_alpha')
                );
                if (in_array($type, $default_alpha)) {
                    $this->set_use_alpha(true, false);
                }
            }
            $name = 'browse_' . $type . '_grid_view';
            if ((isset($_COOKIE[$name]))) {
                $this->set_grid_view(Core::get_cookie($name) == 'true');
            }

            parent::set_type($type, $custom_base);
        } else {
            debug_event(self::class, 'set_type invalid type: ' . $type, 5);
        }
    }

    /**
     *
     * @param string $option
     * @param string $value
     */
    public function save_cookie_params($option, $value): void
    {
        if ($this->get_type()) {
            $remember_length = time() + 31536000;
            $cookie_options  = [
                'expires' => $remember_length,
                'path' => (string)AmpConfig::get('cookie_path'),
                'domain' => (string)AmpConfig::get('cookie_domain'),
                'secure' => make_bool(AmpConfig::get('cookie_secure')),
                'samesite' => 'Strict'
            ];
            setcookie('browse_' . $this->get_type() . '_' . $option, $value, $cookie_options);
        }
    }

    /**
     *
     * @param bool $use_filters
     */
    public function set_use_filters($use_filters): void
    {
        $this->_state['use_filters'] = $use_filters;
    }

    /**
     * is_mashup
     */
    public function is_use_filters(): bool
    {
        if (array_key_exists('use_filters', $this->_state)) {
            return make_bool($this->_state['use_filters']);
        }

        return true;
    }

    /**
     *
     * @param bool $use_pages
     * @param bool $savecookie
     */
    public function set_use_pages($use_pages, $savecookie = true): void
    {
        if ($savecookie) {
            $this->save_cookie_params('pages', $use_pages ? 'true' : 'false');
        }
        $this->_state['use_pages'] = $use_pages;
    }

    /**
     * is_use_pages
     */
    public function is_use_pages(): bool
    {
        if (array_key_exists('use_pages', $this->_state)) {
            return make_bool($this->_state['use_pages']);
        }

        return false;
    }

    /**
     *
     * @param bool $mashup
     */
    public function set_mashup($mashup): void
    {
        $this->_state['mashup'] = $mashup;
    }

    /**
     * is_mashup
     */
    public function is_mashup(): bool
    {
        if (array_key_exists('mashup', $this->_state)) {
            return make_bool($this->_state['mashup']);
        }

        return false;
    }

    /**
     *
     * @param bool $album_artist
     */
    public function set_album_artist($album_artist): void
    {
        $this->_state['album_artist'] = $album_artist;
    }

    /**
     *
     * @param bool $song_artist
     */
    public function set_song_artist($song_artist): void
    {
        $this->_state['song_artist'] = $song_artist;
    }

    /**
     * is_album_artist
     */
    public function is_album_artist(): bool
    {
        if (array_key_exists('album_artist', $this->_state)) {
            return make_bool($this->_state['album_artist']);
        }

        return false;
    }

    /**
     * is_song_artist
     */
    public function is_song_artist(): bool
    {
        if (array_key_exists('song_artist', $this->_state)) {
            return make_bool($this->_state['song_artist']);
        }

        return false;
    }

    /**
     *
     * @param bool $grid_view
     * @param bool $savecookie
     */
    public function set_grid_view($grid_view, $savecookie = true): void
    {
        if ($savecookie) {
            $this->save_cookie_params('grid_view', $grid_view ? 'true' : 'false');
        }
        $this->_state['grid_view'] = $grid_view;
    }

    /**
     * is_grid_view
     */
    public function is_grid_view(): bool
    {
        if (array_key_exists('grid_view', $this->_state)) {
            return make_bool($this->_state['grid_view']);
        }

        return false;
    }

    /**
     *
     * @param bool $use_alpha
     * @param bool $savecookie
     */
    public function set_use_alpha($use_alpha, $savecookie = true): void
    {
        if ($savecookie) {
            $this->save_cookie_params('alpha', $use_alpha ? 'true' : 'false');
        }
        $this->_state['use_alpha'] = $use_alpha;

        if ($use_alpha) {
            if (count($this->_state['filter']) == 0) {
                $this->set_filter('regex_match', '^A');
            }
        } else {
            $this->set_filter('regex_not_match', '');
        }
    }

    /**
     * is_use_alpha
     */
    public function is_use_alpha(): bool
    {
        if (array_key_exists('use_alpha', $this->_state)) {
            return make_bool($this->_state['use_alpha']);
        }

        return false;
    }
    /**
     * Allow the current page to be save into the current session
     * @param bool $update_session
     */
    public function set_update_session($update_session): void
    {
        $this->_state['update_session'] = $update_session;
    }

    /**
     *
     * @param bool $show_header
     */
    public function set_show_header($show_header): void
    {
        $this->_state['show_header'] = $show_header;
    }

    /**
     * is_show_header
     */
    public function is_show_header(): bool
    {
        return $this->_state['show_header'];
    }

    /**
     * is_update_session
     */
    public function is_update_session(): bool
    {
        if (array_key_exists('update_session', $this->_state)) {
            return make_bool($this->_state['update_session']);
        }

        return false;
    }

    /**
     *
     * @param string $threshold
     */
    public function set_threshold($threshold): void
    {
        $this->_state['threshold'] = $threshold;
    }

    /**
     *
     * @param string $title
     */
    public function set_title($title): void
    {
        $this->_state['title'] = $title;
    }

    /**
     * get_threshold
     */
    public function get_threshold(): string
    {
        if (array_key_exists('threshold', $this->_state)) {
            return (string)$this->_state['threshold'];
        }

        return '';
    }

    /**
     * @param string $default
     */
    public function get_title($default): string
    {
        if ($this->_state['title'] !== null) {
            return (string)$this->_state['title'];
        }

        return $default;
    }

    /**
     * get_css_class
     */
    public function get_css_class(): string
    {
        $css = '';
        if (!$this->_state['grid_view']) {
            $css = 'disablegv';
        }

        return $css;
    }

    /**
     * @todo inject by constructor
     */
    private function getShoutRepository(): ShoutRepositoryInterface
    {
        global $dic;

        return $dic->get(ShoutRepositoryInterface::class);
    }

    /**
     * @todo inject by constructor
     */
    private function getShoutObjectLoader(): ShoutObjectLoaderInterface
    {
        global $dic;

        return $dic->get(ShoutObjectLoaderInterface::class);
    }
}
