<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Database\Query;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Browse\ListRenderer\BrowseListContext;
use Ampache\Gui\Browse\ListRenderer\BrowseListRendererLocatorInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;

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
    /**
     * Browse types that lay out a multi-select checkbox column. Only these screens offer the option in the
     * view menu, and only these save a cookie for it.
     */
    public const array MULTISELECT_TYPES = [
        'playlist_media',
        'song',
    ];

    private const array BROWSE_TYPES = [
        'album_disk',
        'album',
        'artist',
        'broadcast',
        'catalog',
        'collection',
        'collection_items',
        'democratic',
        'folder',
        'follower',
        'genre',
        'label',
        'license_hidden',
        'license',
        'live_stream',
        'playlist_localplay',
        'playlist_media',
        'playlist_search',
        'playlist',
        'podcast_episode',
        'podcast',
        'pvmsg',
        'share',
        'shoutbox',
        'smartplaylist',
        'song_preview',
        'song',
        'tag_hidden',
        'tag',
        'user',
        'video',
        'wanted',
    ];

    /** Browse types that can be drawn as tiles, and so remember a grid view choice per browser */
    private const array GRID_TYPES = [
        'album',
        'album_disk',
        'artist',
        'live_stream',
        'playlist',
        'podcast',
        'podcast_episode',
        'smartplaylist',
        'song',
        'video',
    ];

    /** The most rows an un-paged browse may render at once, chosen to stay under a 256M PHP memory limit */
    private const int RENDER_LIMIT = 5000;

    /** Browse types that list rows rather than tiles, whatever the grid view cookie says */
    private const array ROW_TYPES = [
        'collection_items',
        'democratic',
        'genre',
        'playlist_localplay',
        'playlist_media',
    ];
    /**
     * The template each browse type renders through. A type with no entry here renders nothing, so every type in
     * BROWSE_TYPES needs one; the matching box title lives in _getBoxTitle().
     *
     * @var array<string, string>
     */
    private const array TEMPLATE_MAP = [
        'album' => 'show_albums.inc.php',
        'album_disk' => 'show_album_disks.inc.php',
        'artist' => 'show_artists.inc.php',
        'collection' => 'show_collections.inc.php',
        'collection_items' => 'show_collection_items.inc.php',
        'folder' => 'show_folders.inc.php',
        'genre' => 'show_genres.inc.php',
        'playlist' => 'show_playlists.inc.php',
        'playlist_media' => 'show_playlist_medias.inc.php',
        'song' => 'show_songs.inc.php',
        'tag' => 'show_tagcloud.inc.php',
    ];

    public ?int $duration = null;

    public function __construct(
        private readonly AjaxUriRetrieverInterface $ajaxUriRetriever,
        private readonly CollectionRepositoryInterface $collectionRepository,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly GuiFactoryInterface $guiFactory,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
        private readonly UiInterface $ui,
        private readonly UserFollowStateRendererInterface $userFollowStateRenderer,
        private readonly VideoRepositoryInterface $videoRepository,
        private readonly ZipHandlerInterface $zipHandler,
        private readonly BrowseListRendererLocatorInterface $browseListRendererLocator,
        ?int $browse_id = 0,
        ?bool $cached = true,
    ) {
        parent::__construct($browse_id, $cached);

        if (!$browse_id) {
            $this->set_use_pages(true);
            $this->set_use_alpha(false);
            $this->set_grid_view(false);
        }
    }

    /**
     * get_argument_param
     * Rebuild the show_objects argument as query string, so an ajax refresh renders the same columns
     * @param bool|array<string, mixed>|string $argument
     */
    public static function get_argument_param(bool|array|string $argument): string
    {
        if (is_array($argument)) {
            $hide_columns = (array_key_exists('hide', $argument) && is_array($argument['hide']))
                ? $argument['hide']
                : [];

            return ($hide_columns === [])
                ? ''
                : '&hide=' . implode(',', array_map(static fn($column): string => (string) scrub_in((string) $column), $hide_columns));
        }

        return ($argument)
            ? '&argument=' . scrub_in((string) $argument)
            : '';
    }

    /**
     * is_valid_type
     * Validate the browse is a type of object you can actually browse
     */
    public static function is_valid_type(string $type): bool
    {
        return in_array($type, self::BROWSE_TYPES);
    }

    /**
     * add_supplemental_object
     * Legacy function, need to find a better way to do that
     */
    public function add_supplemental_object(string $name, Playlist|Search|Folder|Collection $object): bool
    {
        $_SESSION['browse']['supplemental'][$this->id][$name] = $object;

        return true;
    }

    /**
     * The id of the div this browse renders into, unique per browse so two on a page do not collide.
     */
    public function get_content_div(): string
    {
        $key = 'browse_content_' . $this->get_type();
        if (!empty($this->_state['extended_key_name'])) {
            $key .= '_' . $this->_state['extended_key_name'];
        }

        return $key . ('_' . $this->id);
    }

    /**
     * get_css_class
     */
    public function get_css_class(): string
    {
        return ($this->is_grid_view())
            ? 'gridview'
            : '';
    }

    /**
     * get_supplemental_objects
     * This returns an object so we can reuse it again.
     * @return array<string, Playlist|Search|Folder|Collection>
     */
    public function get_supplemental_objects(): array
    {
        $objects = $_SESSION['browse']['supplemental'][$this->id] ?? '';

        if (!is_array($objects)) {
            $objects = [];
        }

        return $objects;
    }

    /**
     * get_threshold
     */
    public function get_threshold(): string
    {
        return (string) ($this->_state['threshold'] ?? '');
    }

    public function get_title(string $default): string
    {
        return (string) ($this->_state['title'] ?? $default);
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * is_album_artist
     */
    public function is_album_artist(): bool
    {
        return make_bool($this->_state['album_artist'] ?? false);
    }

    /**
     * is_grid_view
     */
    public function is_grid_view(): bool
    {
        return make_bool($this->_state['grid_view'] ?? false);
    }

    /**
     * is_mashup
     */
    public function is_mashup(): bool
    {
        return make_bool($this->_state['mashup'] ?? false);
    }

    /**
     * is_show_header
     */
    public function is_show_header(): bool
    {
        return make_bool($this->_state['show_header'] ?? true);
    }

    /**
     * is_song_artist
     */
    public function is_song_artist(): bool
    {
        return make_bool($this->_state['song_artist'] ?? false);
    }

    /**
     * is_update_session
     */
    public function is_update_session(): bool
    {
        return make_bool($this->_state['update_session'] ?? false);
    }

    /**
     * is_use_alpha
     */
    public function is_use_alpha(): bool
    {
        return (
            $this->is_use_filters()
             && make_bool($this->_state['use_alpha'] ?? false)
        );
    }

    /**
     * is_mashup
     */
    public function is_use_filters(): bool
    {
        return make_bool($this->_state['use_filters'] ?? true);
    }

    /**
     * is_use_pages
     */
    public function is_use_pages(): bool
    {
        return make_bool($this->_state['use_pages'] ?? false);
    }

    /**
     * is_use_select
     *
     * Whether the checkboxes and the action bar of a multi-select browse are shown. Off unless the user asked
     * for them in the view menu; batch actions are not something everyone wants in the way of a track list.
     */
    public function is_use_select(): bool
    {
        return make_bool($this->_state['use_select'] ?? false);
    }

    /**
     * set_album_artist
     */
    public function set_album_artist(bool $album_artist): void
    {
        $this->_state['album_artist'] = $album_artist;
    }

    /**
     * Set an additional content div key.
     * This is used to keep div names unique in the html
     */
    public function set_content_div_ak(int|string $key): void
    {
        $this->_state['extended_key_name'] = str_replace(", ", "_", (string) $key);
    }

    /**
     * set_grid_view
     */
    public function set_grid_view(bool $grid_view, bool $savecookie = true): void
    {
        if ($savecookie && in_array($this->get_type(), ['song', 'album', 'album_disk', 'artist', 'live_stream', 'playlist', 'smartplaylist', 'video', 'podcast', 'podcast_episode'])) {
            $this->save_cookie_params('grid_view', ($grid_view) ? 'true' : 'false');
        }

        $this->_state['grid_view'] = $grid_view;
    }

    /**
     * set_mashup
     */
    public function set_mashup(bool $mashup): void
    {
        $this->_state['mashup'] = $mashup;
    }

    /**
     * set_show_header
     */
    public function set_show_header(bool $show_header): void
    {
        $this->_state['show_header'] = $show_header;
    }

    /**
     * set_song_artist
     */
    public function set_song_artist(bool $song_artist): void
    {
        $this->_state['song_artist'] = $song_artist;
    }

    /**
     * set_threshold
     */
    public function set_threshold(string $threshold): void
    {
        $this->_state['threshold'] = $threshold;
    }

    /**
     * set_title
     */
    public function set_title(string $title): void
    {
        $this->_state['title'] = $title;
    }

    /**
     * This sets the type of object that we want to browse by
     */
    public function set_type(string $type, ?string $custom_base = '', ?array $parameters = []): void
    {
        if (empty($type)) {
            return;
        }

        if ($type === 'album_artist') {
            $this->set_type('artist', $custom_base, $parameters);
            $this->set_album_artist(true);
            $this->set_filter('album_artist', true);

            return;
        }

        if ($type === 'song_artist') {
            $this->set_type('artist', $custom_base, $parameters);
            $this->set_song_artist(true);
            $this->set_filter('song_artist', true);

            return;
        }

        if (self::is_valid_type($type)) {
            // an ajax refresh renders with skip_cookies, so the options it reads before that are restored here
            $use_pages = $this->_readViewCookie($type, 'pages');
            if ($use_pages !== null) {
                $this->set_use_pages($use_pages, false);
            }

            $use_alpha = $this->_readViewCookie($type, 'alpha');
            if ($use_alpha !== null) {
                $this->set_use_alpha($use_alpha, false);
            } else {
                $default_alpha = (AmpConfig::get('libitem_browse_alpha')) ? explode(
                    ",",
                    (string) AmpConfig::get('libitem_browse_alpha')
                ) : [];
                if (in_array($type, $default_alpha)) {
                    $this->set_use_alpha(true, false);
                }
            }

            $use_select = $this->_readViewCookie($type, 'select');
            if ($use_select !== null) {
                $this->set_use_select($use_select, false);
            }

            parent::set_type($type, $custom_base, $parameters);
        } else {
            debug_event(self::class, 'set_type invalid type: ' . $type, 5);
        }
    }

    /**
     * Allow the current page to be saved into the current session
     */
    public function set_update_session(bool $update_session): void
    {
        $this->_state['update_session'] = $update_session;
    }

    /**
     * set_use_alpha
     */
    public function set_use_alpha(bool $use_alpha, bool $savecookie = true): void
    {
        if ($savecookie) {
            $this->save_cookie_params('alpha', ($use_alpha) ? 'true' : 'false');
        }

        $this->_state['use_alpha'] = $use_alpha;

        if (!$use_alpha) {
            $this->set_filter('regex_not_match', '');
        }
    }

    /**
     * set_use_filters
     */
    public function set_use_filters(bool $use_filters): void
    {
        $this->_state['use_filters'] = $use_filters;
    }

    /**
     * set_use_pages
     */
    public function set_use_pages(bool $use_pages, bool $savecookie = true): void
    {
        if ($savecookie) {
            $this->save_cookie_params('pages', ($use_pages) ? 'true' : 'false');
        }

        $this->_state['use_pages'] = $use_pages;
    }

    /**
     * set_use_select
     */
    public function set_use_select(bool $use_select, bool $savecookie = true): void
    {
        if ($savecookie && in_array($this->get_type(), self::MULTISELECT_TYPES)) {
            $this->save_cookie_params('select', ($use_select) ? 'true' : 'false');
        }

        $this->_state['use_select'] = $use_select;
    }

    /**
     * show_next_link
     */
    public function show_next_link(string $argument_param = ''): void
    {
        $limit       = $this->get_offset();
        $start       = $this->get_start();
        $total       = $this->get_total();
        $next_offset = $start + $limit;
        if ($next_offset <= $total) {
            echo '<a class="jscroll-next" href="' . $this->ajaxUriRetriever->getAjaxUri() . '?page=browse&action=page&browse_id=' . $this->id . '&start=' . $next_offset . '&xoutput=raw&xoutputnode=' . $this->get_content_div() . '&show_header=false' . $argument_param . '">' . T_('More') . '</a>';
        }
    }

    /**
     * show_objects
     * This takes an array of objects
     * and requires the correct template based on the
     * type that we are currently browsing
     * @param array<int|string>|array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int}>|array<Song_Preview>|array<int, array{name?: string|null, id: int, track: int, raw: string, link?: string|null, track: int, oid?: int, vlid?: int}>|null $object_ids
     */
    public function show_objects(?array $object_ids = [], bool|array|string $argument = false, ?bool $skip_cookies = false): void
    {
        $type            = $this->get_type();
        $limit_threshold = $this->get_threshold();

        // a song_preview or folder browse is handed rows it built itself, so they are neither saved nor prefetched
        $build_cache = false;
        if ($this->is_simple() || !is_array($object_ids) || $object_ids === []) {
            $object_ids = $this->get_saved();
        } elseif ($type !== 'song_preview' && $type !== 'folder') {
            /** @var array<int|string>|array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int}> $object_ids */
            $this->save_objects($object_ids);
            $build_cache = true;
        }

        $object_ids = $this->_pageObjectIds($object_ids, $type);
        if ($object_ids === null) {
            return;
        }

        if ($build_cache) {
            $this->_prefetchPage($type, $object_ids, $limit_threshold);
        }

        // Row templates are also included directly, so they repeat this prefetch unless told it is already done
        $browse_cached = $build_cache;

        // Load any additional object we need for this
        $extra_objects = $this->get_supplemental_objects();
        $browse        = $this;

        foreach ($extra_objects as $name => $extra) {
            ${$name} = $extra;
        }

        $match = $this->_getMatchName();

        // Update the session value only if it's allowed on the current browser
        if ($this->is_update_session()) {
            $_SESSION['browse_current_' . $type]['start'] = $browse->get_start();
        }

        // Set the correct classes based on type
        $class = "box browse_" . $type . '_' . $this->id;
        debug_event(self::class, 'show_objects called. browse {' . $this->id . '} type {' . $type . '}', 5);

        // hide some of the useless columns in a browse
        $hide_columns = (is_array($argument) && array_key_exists('hide', $argument) && is_array($argument['hide']))
            ? $argument['hide']
            : [];
        $argument_param = self::get_argument_param($argument);

        if (!empty($type) && !$skip_cookies) {
            $this->_applyCookieState($type);
        }

        if (in_array($type, self::ROW_TYPES)) {
            $browse->set_grid_view(false);
        }

        // a migrated type renders through its own renderer, which brings its own services; everything else
        // reaches them through the local scope built further down
        $renderer  = $this->browseListRendererLocator->find($type);
        $box_req   = ($renderer === null) ? $this->_getTemplate($type) : '';
        $box_title = $this->_getBoxTitle($type, $match);

        // an album list may be titled and grouped by whatever asked for it
        $group_release = false;
        if (is_array($argument) && ($type === 'album' || $type === 'album_disk')) {
            $box_title     = (string) ($argument['title'] ?? $box_title);
            $group_release = (bool) ($argument['group_disks'] ?? false);
        }

        Ajax::start_container($this->get_content_div(), 'browse_content');
        $hasBody = $renderer !== null || $box_req !== '';
        if ($this->is_show_header() && $hasBody && $box_title !== '') {
            $this->set_title($box_title);
            Ui::show_box_top($box_title, $class);
        }

        if ($renderer !== null) {
            echo $renderer->renderList(
                new BrowseListContext(
                    $this,
                    $object_ids,
                    $hide_columns,
                    $argument_param,
                    $limit_threshold,
                    $browse_cached,
                    $group_release
                )
            );
        } elseif ($box_req !== '') {
            // the browse template and its row templates render in this scope, so the services they use are named here
            $ajaxUriRetriever        = $this->ajaxUriRetriever;
            $collectionRepository    = $this->collectionRepository;
            $gatekeeper              = $this->gatekeeperFactory->createGuiGatekeeper();
            $guiFactory              = $this->guiFactory;
            $libraryItemLoader       = $this->libraryItemLoader;
            $ui                      = $this->ui;
            $userFollowStateRenderer = $this->userFollowStateRenderer;
            $videoRepository         = $this->videoRepository;
            $zipHandler              = $this->zipHandler;

            require $box_req;
        }

        if ($this->is_show_header()) {
            if ($hasBody) {
                Ui::show_box_bottom();
            }

            if ($this->is_use_filters()) {
                echo '<script>';
                echo Ajax::action('?page=browse&action=get_filters&browse_id=' . $this->id . $argument_param, '');
                echo ';</script>';
            }
        } elseif (!$this->is_use_pages()) {
            $this->show_next_link($argument_param);
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
     * update_browse_from_session
     * Restore the previous start index from something saved into the current session.
     */
    public function update_browse_from_session(): void
    {
        if ($this->is_simple() && $this->get_start() == 0) {
            $name = 'browse_current_' . $this->get_type();
            if (array_key_exists($name, $_SESSION) && array_key_exists('start', $_SESSION[$name]) && $_SESSION[$name]['start'] > 0) {
                // Checking if value is suitable
                $start = (int) $_SESSION[$name]['start'];
                if ($this->get_offset() > 0) {
                    $set_page    = floor($start / $this->get_offset());
                    $total_pages = ($this->get_total() > $this->get_offset())
                        ? ceil($this->get_total() / $this->get_offset())
                        : 0;

                    if ($set_page >= 0 && $set_page <= $total_pages) {
                        $this->set_start($start);
                    }
                }
            }
        }
    }

    /**
     * Restore the view options this browser last chose for this type.
     */
    private function _applyCookieState(string $type): void
    {
        $use_pages = $this->_readViewCookie($type, 'pages');
        if (!$this->is_mashup() && $use_pages !== null) {
            $this->set_use_pages($use_pages, false);
        }

        $grid_view = $this->_readViewCookie($type, 'grid_view');
        if (in_array($type, self::GRID_TYPES)) {
            if (!$this->is_mashup() && $grid_view !== null) {
                $this->set_grid_view($grid_view, false);
            }
        } else {
            $this->set_grid_view(false);
        }

        $use_alpha = $this->_readViewCookie($type, 'alpha');
        if ($this->is_use_filters() && $use_alpha !== null) {
            $this->set_use_alpha($use_alpha, false);
        }

        $use_select = $this->_readViewCookie($type, 'select');
        if (in_array($type, self::MULTISELECT_TYPES) && $use_select !== null) {
            $this->set_use_select($use_select, false);
        }
    }

    /**
     * The translated box title for this type, carrying the filter name where the list is filtered by one.
     */
    private function _getBoxTitle(string $type, string $match): string
    {
        $title = match ($type) {
            'album', 'album_disk' => T_('Albums') . $match,
            'artist' => match (true) {
                $this->is_album_artist() => T_('Album Artist') . $match,
                $this->is_song_artist() => T_('Song Artist') . $match,
                default => T_('Artist') . $match,
            },
            'broadcast' => T_('Broadcasts'),
            'catalog' => T_('Catalogs'),
            'collection' => T_('Collections') . $match,
            'collection_items' => T_('Collection Items') . $match,
            'democratic' => T_('Democratic Playlist'),
            'folder' => T_('Folders'),
            'follower', 'user' => T_('Browse Users') . $match,
            'genre' => T_('Genres') . $match,
            'label' => T_('Labels'),
            'license', 'license_hidden' => T_('Media Licenses'),
            'live_stream' => T_('Radio Stations') . $match,
            'playlist' => T_('Playlists') . $match,
            'playlist_localplay' => T_('Current Playlist'),
            'playlist_media' => T_('Playlist Items') . $match,
            'playlist_search', 'smartplaylist' => T_('Smart Playlists') . $match,
            'podcast' => T_('Podcasts'),
            'podcast_episode' => T_('Podcast Episodes'),
            'pvmsg' => T_('Private Messages'),
            'share' => T_('Shares'),
            'shoutbox' => T_('Shoutbox Records'),
            'song' => T_('Songs') . $match,
            'song_preview' => T_('Songs'),
            'tag', 'tag_hidden' => T_('Genres'),
            'video' => T_('Videos'),
            'wanted' => T_('Wanted Albums'),
            default => '',
        };

        return $this->get_title($title);
    }

    /**
     * The name of whatever the list is filtered by, ready to append to the box title.
     */
    private function _getMatchName(): string
    {
        if ($filter_value = $this->get_filter('alpha_match')) {
            return ' (' . $filter_value . ')';
        }

        if ($filter_value = $this->get_filter('starts_with')) {
            return ' (' . $filter_value . ')';
        }

        if ($filter_value = $this->get_filter('catalog')) {
            $catalog = Catalog::create_from_id((int) $filter_value);
            if ($catalog !== null) {
                return ' (' . $catalog->name . ')';
            }
        }

        return '';
    }

    /**
     * The template this type renders through, or an empty string when the type has none.
     */
    private function _getTemplate(string $type): string
    {
        if (!array_key_exists($type, self::TEMPLATE_MAP)) {
            debug_event(self::class, 'show_objects: no template for browse type {' . $type . '}', 1);

            return '';
        }

        return Ui::find_template(self::TEMPLATE_MAP[$type]);
    }

    /**
     * Cut the id list down to the page being rendered. An un-paged list too large to hold in memory renders an
     * error instead of failing silently, which is signalled back by a null return.
     *
     * @param int[]|string[]|array<array{object_id: int, object_type: LibraryItemEnum|string, track_id: int, track: int}>|array<int, array{name?: string|null, id: int, track: int, raw: string, link?: string|null, track: int, oid?: int, vlid?: int}>|array<Song_Preview> $object_ids
     * @return int[]|string[]|array<array{object_id: int, object_type: LibraryItemEnum|string, track_id: int, track: int}>|array<int, array{name?: string|null, id: int, track: int, raw: string, link?: string|null, track: int, oid?: int, vlid?: int}>|array<Song_Preview>|null
     */
    private function _pageObjectIds(array $object_ids, string $type): ?array
    {
        if ($this->get_offset() > 0 && $this->get_start() >= 0 && !$this->is_simple()) {
            return array_slice($object_ids, $this->get_start(), $this->get_offset(), true);
        }

        if ($object_ids === []) {
            $this->set_total(0);

            return $object_ids;
        }

        // an un-paged browse builds every row at once, so a hard ceiling keeps it under PHP's memory limit (#4276)
        $count = count($object_ids);
        if (!$this->is_simple() && $this->get_offset() === 0 && $count > self::RENDER_LIMIT) {
            debug_event(self::class, sprintf('show_objects refused: un-paged %s browse of %d objects exceeds the %d render limit', $type, $count, self::RENDER_LIMIT), 1);
            $message = sprintf(nT_('This view has %d item and is too large to show all at once (limit %d). Enable paging or narrow your filters.', 'This view has %d items and is too large to show all at once (limit %d). Enable paging or narrow your filters.', $count), $count, self::RENDER_LIMIT);
            AmpError::add('browse', $message);

            echo '<div class="error browse-too-large">' . scrub_out($message) . '</div>';

            return null;
        }

        return $object_ids;
    }

    /**
     * Warm the object cache for the rows this page shows, so the row templates read them without a query each.
     *
     * @param int[]|string[]|array<array{object_id: int, object_type: LibraryItemEnum|string, track_id: int, track: int}>|array<int, array{name?: string|null, id: int, track: int, raw: string, link?: string|null, track: int, oid?: int, vlid?: int}>|array<Song_Preview> $object_ids
     */
    private function _prefetchPage(string $type, array $object_ids, string $limit_threshold): void
    {
        /** @var array<int|string>|array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int}> $object_ids */
        match ($type) {
            'song' => Song::build_cache($this->_squashList($object_ids), $limit_threshold),
            'album' => Album::build_cache($this->_squashList($object_ids)),
            'artist' => Artist::build_cache($this->_squashList($object_ids), true, $limit_threshold),
            'playlist' => Playlist::build_cache($this->_squashList($object_ids)),
            'genre', 'tag', 'tag_hidden' => Tag::build_cache($this->_squashList($object_ids)),
            'video' => Video::build_cache($this->_squashList($object_ids)),
            default => null,
        };
    }

    /**
     * What this browser last chose for one of the browse view options, or null where it has never chosen one.
     * Reading an option never writes it back, so every caller passes false for the cookie save.
     */
    private function _readViewCookie(string $type, string $option): ?bool
    {
        $name = 'browse_' . $type . '_' . $option;

        return array_key_exists($name, $_COOKIE)
            ? Core::get_cookie($name) === 'true'
            : null;
    }

    /**
     * @param array<int|string>|array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int}> $object_ids
     * @return array<int|string>
     */
    private function _squashList(array $object_ids): array
    {
        if ($object_ids === []) {
            return [];
        }

        $results = [];
        foreach ($object_ids as $value) {
            if (is_int($value) || is_string($value)) {
                $results[] = $value;
            }
            if (is_array($value)) {
                $results[] = $value['object_id'];
            }
        }

        return $results;
    }

    /**
     * save_cookie_params
     */
    private function save_cookie_params(string $option, string $value): void
    {
        if ($this->get_type() !== '' && $this->get_type() !== '0') {
            $remember_length = time() + 31536000;
            $cookie_options  = [
                'expires' => $remember_length,
                'path' => (string) AmpConfig::get('cookie_path'),
                'domain' => (string) AmpConfig::get('cookie_domain'),
                'secure' => make_bool(AmpConfig::get('cookie_secure')),
                'samesite' => 'Strict',
            ];
            setcookie('browse_' . $this->get_type() . '_' . $option, $value, $cookie_options);
        }
    }
}
