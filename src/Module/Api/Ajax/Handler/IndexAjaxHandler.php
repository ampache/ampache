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

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Artist\ArtistInfoView;
use Ampache\Gui\Artist\RecommendedArtistsView;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\Index\NowPlayingSimilarView;
use Ampache\Gui\Index\RandomAlbumsView;
use Ampache\Gui\Index\RandomVideosView;
use Ampache\Gui\Song\SongListPanelView;
use Ampache\Gui\Wanted\MissingAlbumsView;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\Util\Recommendation;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\SlideshowInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Module\Wanted\WantedManagerInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Wanted;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;

final readonly class IndexAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private SlideshowInterface $slideshow,
        private AlbumRepositoryInterface $albumRepository,
        private LabelRepositoryInterface $labelRepository,
        private SongRepositoryInterface $songRepository,
        private WantedRepositoryInterface $wantedRepository,
        private VideoRepositoryInterface $videoRepository,
        private WantedManagerInterface $wantedManager,
        private GatekeeperFactoryInterface $gatekeeperFactory,
        private GuiFactoryInterface $guiFactory,
        private ZipHandlerInterface $zipHandler,
        private BrowseFactoryInterface $browseFactory,
        private FolderRepositoryInterface $folderRepository,
    ) {}

    public function handle(User $user): void
    {
        $results = [];
        $action  = $this->requestParser->getFromRequest('action');
        $moment  = (int) AmpConfig::get('of_the_moment');
        // filter album and video of the Moment instead of a hardcoded value
        if (!$moment > 0) {
            $moment = 6;
        }

        // Switch on the actions
        switch ($action) {
            case 'top_tracks':
                $artist                = new Artist((int) $this->requestParser->getFromRequest('artist'));
                $object_ids            = $this->songRepository->getTopSongsByArtist($artist, (int) AmpConfig::get('popular_threshold', 10));
                $results['top_tracks'] = $this->createSongListPanelView(
                    'top_tracks',
                    $object_ids,
                    ['cel_artist'],
                    'topTracksIndexes();'
                )->render();
                break;
            case 'random_albums':
                $albums = $this->albumRepository->getRandom(
                    $user->id ?: -1,
                    $moment
                );
                if ($albums !== []) {
                    $results['random_selection'] = $this->createRandomAlbumsView(LibraryItemEnum::ALBUM, $albums)->render();
                } else {
                    $results['random_selection'] = '<!-- None found -->';

                    if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
                        $catalogs = Catalog::get_all_catalogs();
                        if ($catalogs === []) {
                            /* HINT: %1 and %2 surround "add a Catalog" to make it into a link */
                            $results['random_selection'] = sprintf(
                                T_('No Catalog configured yet. To start streaming your media, you now need to %1$s add a Catalog %2$s'),
                                '<a href="' . AmpConfig::get_web_path('/admin') . '/catalog.php?action=show_add_catalog">',
                                '</a>.<br /><br />'
                            );
                        }
                    }
                }

                break;
            case 'random_album_disks':
                $albumDisks = $this->albumRepository->getRandomAlbumDisk(
                    $user->id ?: -1,
                    $moment
                );
                if ($albumDisks !== []) {
                    $results['random_selection'] = $this->createRandomAlbumsView(LibraryItemEnum::ALBUM_DISK, $albumDisks)->render();
                } else {
                    $results['random_selection'] = '<!-- None found -->';

                    if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
                        $catalogs = Catalog::get_all_catalogs();
                        if ($catalogs === []) {
                            /* HINT: %1 and %2 surround "add a Catalog" to make it into a link */
                            $results['random_selection'] = sprintf(
                                T_('No Catalog configured yet. To start streaming your media, you now need to %1$s add a Catalog %2$s'),
                                '<a href="' . AmpConfig::get_web_path('/admin') . '/catalog.php?action=show_add_catalog">',
                                '</a>.<br /><br />'
                            );
                        }
                    }
                }

                break;
            case 'random_videos':
                $videos = $this->videoRepository->getRandom(
                    $user->id ?: -1,
                    $moment
                );
                if ($videos !== []) {
                    $results['random_video_selection'] = (new RandomVideosView(
                        $videos,
                        Ui::is_grid_view('video'),
                        (bool) AmpConfig::get('directplay'),
                        Stream_Playlist::check_autoplay_next(),
                        Stream_Playlist::check_autoplay_append(),
                        Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) && (bool) AmpConfig::get('ratings')
                    ))->render();
                } else {
                    $results['random_video_selection'] = '<!-- None found -->';
                }

                break;
            case 'artist_info':
                if (AmpConfig::get('lastfm_api_key') && (array_key_exists('artist', $_REQUEST) || array_key_exists('fullname', $_REQUEST))) {
                    if (array_key_exists('artist', $_REQUEST)) {
                        $artist    = new Artist((int) $this->requestParser->getFromRequest('artist'));
                        $biography = Recommendation::get_artist_info($artist->id);
                    } else {
                        $fullname  = $this->requestParser->getFromRequest('fullname');
                        $artist    = $this->wantedRepository->findByName($fullname);
                        $biography = Recommendation::get_artist_info_by_name(rawurldecode($fullname));
                    }

                    $results['artist_biography'] = (new ArtistInfoView($artist, $biography))->render();
                }

                break;
            case 'similar_artist':
                if (AmpConfig::get('show_similar') && array_key_exists('artist', $_REQUEST)) {
                    $artist          = new Artist((int) $this->requestParser->getFromRequest('artist'));
                    $limit_threshold = AmpConfig::get('stats_threshold', 7);
                    $object_ids      = [];
                    $missing_objects = [];
                    if ($similars = Recommendation::get_artists_like($artist->id, 10, !AmpConfig::get('wanted'))) {
                        foreach ($similars as $similar) {
                            if (!empty($similar['id'])) {
                                $object_ids[] = (int) $similar['id'];
                            } elseif (!empty($similar['mbid'])) {
                                $missing_objects[] = $similar;
                            }
                        }
                    }

                    $mayInteract = !AmpConfig::get('use_auth')
                        || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);

                    $results['similar_artist'] = (new RecommendedArtistsView(
                        $this->gatekeeperFactory->createGuiGatekeeper(),
                        AmpConfig::get_web_path(),
                        $object_ids,
                        $missing_objects,
                        0,
                        false,
                        (bool) AmpConfig::get('hide_genres'),
                        User::is_registered() && (bool) AmpConfig::get('ratings'),
                        (bool) AmpConfig::get('show_played_times'),
                        (bool) AmpConfig::get('directplay'),
                        (int) AmpConfig::get('direct_play_limit', 500),
                        $mayInteract,
                        (bool) AmpConfig::get('sociable'),
                        false
                    ))->render();
                }

                break;
            case 'similar_songs':
                $artist     = new Artist((int) $this->requestParser->getFromRequest('artist'));
                $similars   = Recommendation::get_artists_like($artist->id);
                $object_ids = [];
                foreach ($similars as $similar) {
                    if ($similar['id']) {
                        $similar_artist = new Artist($similar['id']);
                        // get the songs in a random order for even more chaos
                        $object_ids = array_merge($object_ids, $this->songRepository->getRandomByArtist($similar_artist));
                    }
                }

                // randomize and slice
                shuffle($object_ids);
                $object_ids               = array_slice($object_ids, 0, (int) AmpConfig::get('popular_threshold', 10));
                $results['similar_songs'] = $this->createSongListPanelView(
                    'similar_songs',
                    $object_ids,
                    []
                )->render();
                break;
            case 'similar_now_playing':
                $media_id = (int) $this->requestParser->getFromRequest('media_id');
                if (AmpConfig::get('show_similar') && $media_id > 0 && array_key_exists('media_artist', $_REQUEST)) {
                    $artists = Recommendation::get_artists_like((int) $this->requestParser->getFromRequest('media_artist'), 3, false);
                    $songs   = Recommendation::get_songs_like($media_id, 3);
                    ob_start();
                    echo (new NowPlayingSimilarView(
                        AmpConfig::get_web_path(),
                        $artists,
                        $songs,
                        (bool) AmpConfig::get('wanted')
                    ))->render();
                    $results['similar_items_' . $media_id] = ob_get_clean();
                }

                break;
            case 'labels':
                if (AmpConfig::get('label') && array_key_exists('artist', $_REQUEST)) {
                    $labels     = $this->labelRepository->getByArtist((int) $this->requestParser->getFromRequest('artist'));
                    $object_ids = [];
                    foreach ($labels as $labelid => $label) {
                        $object_ids[] = $labelid;
                    }

                    $browse = $this->browseFactory->create();
                    $browse->set_type('label');
                    $browse->set_simple_browse(false);
                    $browse->save_objects($object_ids);
                    $browse->store();
                    ob_start();
                    $labelRepository = $this->labelRepository;
                    require_once Ui::find_template('show_labels.inc.php');
                    $results['labels'] = ob_get_clean();
                }

                break;
            case 'wanted_missing_albums':
                if (AmpConfig::get('wanted') && (array_key_exists('artist', $_REQUEST) || array_key_exists('artist_mbid', $_REQUEST))) {
                    $walbums = [];
                    if (array_key_exists('artist', $_REQUEST)) {
                        $artist = new Artist((int) $this->requestParser->getFromRequest('artist'));
                        if (
                            !in_array($artist->mbid, [null, '', '0'], true)
                        ) {
                            $walbums = Wanted::get_missing_albums($artist);
                        } else {
                            debug_event('index.ajax', 'Cannot get missing albums: MusicBrainz ID required.', 3);
                        }
                    } elseif (array_key_exists('artist_mbid', $_REQUEST)) {
                        $walbums = Wanted::get_missing_albums(null, $_REQUEST['artist_mbid']);
                    }

                    ob_start();
                    echo (new MissingAlbumsView($walbums))->render();
                    $results['missing_albums'] = ob_get_clean();
                }

                break;
            case 'add_wanted':
                if (AmpConfig::get('wanted') && array_key_exists('mbid', $_REQUEST)) {
                    $mbid = $this->requestParser->getFromRequest('mbid');
                    if (!array_key_exists('artist', $_REQUEST)) {
                        $artist_mbid = $_REQUEST['artist_mbid'] ?? null;
                        $artist      = null;
                    } else {
                        $artist      = (int) $this->requestParser->getFromRequest('artist');
                        $aobj        = new Artist($artist);
                        $artist_mbid = $aobj->mbid;
                    }

                    $name = $this->requestParser->getFromRequest('name');
                    $year = (int) $this->requestParser->getFromRequest('year');

                    if (!$this->wantedRepository->find($mbid, $user)) {
                        $this->wantedManager->add(
                            $user,
                            $mbid,
                            $artist,
                            $artist_mbid,
                            $name,
                            $year
                        );

                        $walbum = $this->wantedRepository->findByMusicBrainzId($mbid);
                        if ($walbum !== null) {
                            $results['wanted_action_' . $mbid] = $walbum->show_action_buttons();
                        }
                    } else {
                        debug_event('index.ajax', 'Already wanted, skipped.', 5);
                    }
                }

                break;
            case 'remove_wanted':
                if (AmpConfig::get('wanted') && array_key_exists('mbid', $_REQUEST)) {
                    $mbid   = $this->requestParser->getFromRequest('mbid');
                    $walbum = $this->wantedRepository->findByMusicBrainzId($mbid);

                    $this->wantedRepository->deleteByMusicbrainzId(
                        $mbid,
                        ($user->has_access(AccessLevelEnum::MANAGER)) ? null : $user
                    );

                    if ($walbum !== null) {
                        $walbum->accepted = 0;
                        $walbum->id       = 0;

                        $results['wanted_action_' . $mbid] = $walbum->show_action_buttons();
                    }
                }

                break;
            case 'accept_wanted':
                if (AmpConfig::get('wanted') && array_key_exists('mbid', $_REQUEST)) {
                    $mbid = $this->requestParser->getFromRequest('mbid');

                    $walbum = $this->wantedRepository->findByMusicBrainzId($mbid);

                    if ($walbum !== null) {
                        $this->wantedManager->accept($walbum, $user);

                        $results['wanted_action_' . $mbid] = $walbum->show_action_buttons();
                    }
                }

                break;
            case 'delete_play':
                if (
                    check_http_referer() === true
                    && Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
                    && isset($_REQUEST['activity_id'])
                ) {
                    Stats::delete((int) $_REQUEST['activity_id']);
                }

                ob_start();
                $user_id   = $user->id ?: -1;
                $ajax_page = 'index';
                if (AmpConfig::get('home_recently_played_all')) {
                    $data = Stats::get_recently_played($user_id);
                    require_once Ui::find_template('show_recently_played_all.inc.php');
                } else {
                    $data = Stats::get_recently_played($user_id, 'stream', 'song');
                    Song::build_cache(array_keys($data));
                    require Ui::find_template('show_recently_played.inc.php');
                }

                $results['recently_played'] = ob_get_clean();
                break;
            case 'ignore_update':
                // The ajax entry point has no gatekeeper, so repeat the admin gate update.php's clear action applies
                if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) {
                    AutoUpdate::clear_status();
                    $results['autoupdate'] = '';
                }

                break;
            case 'refresh_now_playing':
                ob_start();
                show_now_playing();
                $results['now_playing'] = ob_get_clean();
                break;
            case 'refresh_index':
                ob_start();
                show_now_playing();
                $results['now_playing'] = ob_get_clean();
                ob_start();
                $user_id = (isset($_REQUEST['user_id']))
                    ? (int) $this->requestParser->getFromRequest('user_id')
                    : ($user->id ?: -1);
                $user_only = isset($_REQUEST['user_only']);
                $ajax_page = 'index';
                if (AmpConfig::get('home_recently_played_all')) {
                    $data = Stats::get_recently_played($user_id);
                    require_once Ui::find_template('show_recently_played_all.inc.php');
                } else {
                    $data = Stats::get_recently_played($user_id, 'stream', 'song');
                    Song::build_cache(array_keys($data));
                    require Ui::find_template('show_recently_played.inc.php');
                }

                $results['recently_played'] = ob_get_clean();
                break;
            case 'dashboard_newest':
            case 'dashboard_popular':
            case 'dashboard_random':
            case 'dashboard_recent':
            case 'dashboard_trending':
                $limit       = (int) ($_REQUEST['limit'] ?? 0);
                $object_type = (string) ($_REQUEST['object_type'] ?? '');
                $threshold   = (int) ($_REQUEST['threshold'] ?? 0);
                ob_start();
                $object_ids = [];
                if ($action === 'dashboard_random') {
                    $object_ids = (AmpConfig::get('album_group'))
                        ? $this->albumRepository->getRandom($user->id, $limit)
                        : $this->albumRepository->getRandomAlbumDisk($user->id, $limit);
                }

                if ($object_ids !== []) {
                    $browse = $this->browseFactory->create();
                    $browse->set_type($object_type);
                    $browse->set_use_filters(false);
                    $browse->set_show_header(false);
                    $browse->set_grid_view(true, false);
                    $browse->set_mashup(true);
                    $browse->show_objects($object_ids);
                }

                $object_ids = ($action === 'dashboard_newest')
                    ? Stats::get_newest($object_type, $limit, 0, 0, $user)
                    : [];
                if ($object_ids !== []) {
                    $browse = $this->browseFactory->create();
                    $browse->set_type($object_type);
                    $browse->set_use_filters(false);
                    $browse->set_show_header(false);
                    $browse->set_grid_view(true, false);
                    $browse->set_mashup(true);
                    $browse->show_objects($object_ids);
                }

                $object_ids = ($action === 'dashboard_recent')
                    ? Stats::get_recent($object_type, $limit)
                    : [];
                if ($object_ids !== []) {
                    $browse = $this->browseFactory->create();
                    $browse->set_type($object_type);
                    $browse->set_use_filters(false);
                    $browse->set_show_header(false);
                    $browse->set_grid_view(true, false);
                    $browse->set_mashup(true);
                    $browse->show_objects($object_ids);
                }

                $object_ids = ($action === 'dashboard_trending')
                    ? Stats::get_top($object_type, $limit, $threshold)
                    : [];
                if ($object_ids !== []) {
                    $browse = $this->browseFactory->create();
                    $browse->set_type($object_type);
                    $browse->set_use_filters(false);
                    $browse->set_show_header(false);
                    $browse->set_grid_view(true, false);
                    $browse->set_mashup(true);
                    $browse->show_objects($object_ids);
                }

                $object_ids = ($action === 'dashboard_popular')
                    ? Stats::get_top($object_type, 100, $threshold, 0, ($user->getId() > 0) ? $user : null)
                    : [];
                if ($object_ids !== []) {
                    shuffle($object_ids);
                    $object_ids = array_slice($object_ids, 0, $limit);
                    $browse     = $this->browseFactory->create();
                    $browse->set_type($object_type);
                    $browse->set_use_filters(false);
                    $browse->set_show_header(false);
                    $browse->set_grid_view(true, false);
                    $browse->set_mashup(true);
                    $browse->show_objects($object_ids);
                }

                $results[$action] = ob_get_clean();
                break;
            case 'sidebar':
                switch ($_REQUEST['button'] ?? '') {
                    case 'home':
                    case 'modules':
                    case 'localplay':
                    case 'player':
                    case 'preferences':
                        $button = $_REQUEST['button'];
                        break;
                    case 'admin':
                        if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
                            $button = $_REQUEST['button'];
                        } else {
                            return;
                        }

                        break;
                    default:
                        return;
                }

                Ajax::set_include_override(true);
                ob_start();
                $_SESSION['state']['sidebar_tab'] = $button;
                // sidebar_home renders in this scope
                $folderRepository = $this->folderRepository;

                $videoRepository = $this->videoRepository;
                require_once Ui::find_template('sidebar.inc.php');
                $results['sidebar-content'] = ob_get_contents();
                ob_end_clean();
                break;
            case 'slideshow':
                ob_start();
                $images = $this->slideshow->getCurrentSlideshow($user);
                if ($images !== []) {
                    $fsname = 'fslider_' . time();
                    echo "<div id='" . $fsname . "'>";
                    foreach ($images as $image) {
                        echo "<img src='" . $image['url'] . "' alt= '' onclick='update_action();' />";
                    }

                    echo "</div>";
                    $results['fslider'] = ob_get_clean();
                    ob_start();
                    echo '<script>';
                    echo "$('#" . $fsname . "').rhinoslider({
                    showTime: 15000,
                    effectTime: 2000,
                    randomOrder: true,
                    controlsPlayPause: false,
                    autoPlay: true,
                    showBullets: 'never',
                    showControls: 'always',
                    controlsMousewheel: false,
            });";
                    echo "</script>";
                }

                $results['fslider_script'] = ob_get_clean();
                break;
            case 'albums':
                $label_id = (int) ($_REQUEST['label'] ?? 0);

                ob_start();
                if ($label_id > 0) {
                    $label = $this->labelRepository->findById($label_id);

                    // the label tag describes the release, so the albums come from `label_asso` rather than a text match
                    $object_ids = ($label === null)
                        ? []
                        : $label->get_albums();

                    $browse = $this->browseFactory->create();
                    $browse->set_type('album');
                    $browse->set_simple_browse(false);
                    $browse->set_use_filters(false);
                    $browse->show_objects($object_ids, true);
                    $browse->set_use_alpha(false, false);
                    $browse->store();
                }

                $results['albums'] = ob_get_contents();
                ob_end_clean();
                break;
            case 'songs':
                $label_id = (int) ($_REQUEST['label'] ?? 0);

                ob_start();
                if ($label_id > 0) {
                    $label = $this->labelRepository->findById($label_id);

                    $object_ids = ($label === null)
                        ? []
                        : $this->songRepository->getByLabel((string) $label->name);

                    $browse = $this->browseFactory->create();
                    $browse->set_type('song');
                    $browse->set_simple_browse(false);
                    $browse->save_objects($object_ids);
                    $browse->store();

                    $hide_columns = [];
                    Ui::show_box_top(T_('Songs'), 'info-box');
                    $gatekeeper = $this->gatekeeperFactory->createGuiGatekeeper();
                    $guiFactory = $this->guiFactory;
                    $zipHandler = $this->zipHandler;
                    require_once Ui::find_template('show_songs.inc.php');
                    Ui::show_box_bottom();
                }

                $results['songs'] = ob_get_contents();
                ob_end_clean();
        } // switch on action;

        // We always do this
        echo xoutput_from_array($results);
    }

    /**
     * @param list<int> $objectIds
     */
    private function createRandomAlbumsView(LibraryItemEnum $type, array $objectIds): RandomAlbumsView
    {
        return new RandomAlbumsView(
            $type,
            $objectIds,
            Ui::is_grid_view('album'),
            (bool) AmpConfig::get('directplay'),
            Stream_Playlist::check_autoplay_next(),
            Stream_Playlist::check_autoplay_append(),
            Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) && (bool) AmpConfig::get('ratings')
        );
    }

    /**
     * @param list<int> $songIds
     * @param list<string> $hiddenColumns
     */
    private function createSongListPanelView(
        string $tableId,
        array $songIds,
        array $hiddenColumns,
        ?string $onRenderScript = null,
    ): SongListPanelView {
        return new SongListPanelView(
            $this->guiFactory,
            $this->gatekeeperFactory->createGuiGatekeeper(),
            $tableId,
            $songIds,
            $hiddenColumns,
            User::is_registered() && (bool) AmpConfig::get('ratings'),
            (bool) AmpConfig::get('hide_genres'),
            (bool) AmpConfig::get('album_group'),
            (bool) AmpConfig::get('licensing') && (bool) AmpConfig::get('show_license'),
            (bool) AmpConfig::get('show_played_times'),
            (bool) AmpConfig::get('show_skipped_times'),
            Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
            $onRenderScript
        );
    }
}
