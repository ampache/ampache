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

namespace Ampache\Module\Api\Edit;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Artist\ArtistRowView;
use Ampache\Gui\Broadcast\BroadcastRowView;
use Ampache\Gui\Genre\GenreRowView;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\Label\LabelRowView;
use Ampache\Gui\LiveStream\LiveStreamRowView;
use Ampache\Gui\Podcast\PodcastEpisodeRowView;
use Ampache\Gui\Podcast\PodcastRowView;
use Ampache\Gui\Search\SearchRowView;
use Ampache\Gui\Share\ShareRowView;
use Ampache\Gui\Song\SongPreviewRowView;
use Ampache\Gui\Video\VideoRowView;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Repository\ShareRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class RefreshUpdatedAction extends AbstractEditAction
{
    public const string REQUEST_KEY = 'refresh_updated';

    private AjaxUriRetrieverInterface $ajaxUriRetriever;
    private Browse $browse;
    private GuiFactoryInterface $guiFactory;
    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;
    private ZipHandlerInterface $zipHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LibraryItemLoaderInterface $libraryItemLoader,
        LoggerInterface $logger,
        ShareRepositoryInterface $shareRepository,
        BrowseFactoryInterface $browseFactory,
        GuiFactoryInterface $guiFactory,
        Browse $browse,
        AjaxUriRetrieverInterface $ajaxUriRetriever,
        ZipHandlerInterface $zipHandler,
    ) {
        parent::__construct($configContainer, $libraryItemLoader, $logger, $shareRepository, $browseFactory);
        $this->responseFactory   = $responseFactory;
        $this->streamFactory     = $streamFactory;
        $this->guiFactory        = $guiFactory;
        $this->browse            = $browse;
        $this->ajaxUriRetriever  = $ajaxUriRetriever;
        $this->zipHandler        = $zipHandler;
    }

    /**
     * handle
     *
     * Templates that aren't edited
     * * catalog_row
     * * now_playing_row
     * * now_playing_video_row
     * * playlist_media_row
     *
     * Templates that redirect and are not refreshed here
     * * filter_row
     * * license_row
     * * shout_row
     * * user_row
     */
    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        library_item|Share $libitem,
        int $object_id,
        ?Browse $browse = null,
    ): ResponseInterface {
        $show_ratings = User::is_registered() && AmpConfig::get('ratings');
        /**
         * @todo Every editable item type will need some sort of special handling here
         */
        switch ($object_type) {
            case 'song_row':
                /** @var Song $libitem */
                $hide_genres    = (bool) AmpConfig::get('hide_genres');
                $is_group       = (bool) AmpConfig::get('album_group');
                $show_license   = (bool) (AmpConfig::get('licensing') && AmpConfig::get('show_license'));
                $hide           = Core::get_request('hide');
                $argument_param = '&hide=' . $hide;
                $argument       = explode(',', $hide);
                $hide_artist    = in_array('cel_artist', $argument);
                $hide_album     = in_array('cel_album', $argument);
                $hide_year      = in_array('cel_year', $argument);
                $hide_drag      = in_array('cel_drag', $argument);
                $results        = $this->guiFactory->createSongRowView(
                    $gatekeeper,
                    $libitem,
                    $argument_param,
                    $show_ratings,
                    true,
                    $is_group,
                    (!empty($hide)),
                    $show_license,
                    $hide_genres,
                    $hide_artist,
                    $hide_album,
                    $hide_year,
                    $hide_drag
                )->render();
                break;
            case 'playlist_row':
                /** @var Playlist $libitem */
                $show_art = (bool) AmpConfig::get('playlist_art');
                $results  = $this->guiFactory->createPlaylistRowView(
                    $gatekeeper,
                    $libitem,
                    User::is_registered() && (AmpConfig::get('ratings')),
                    $show_art,
                    true,
                    'cel_cover'
                )->render();
                break;
            case 'album_row':
                /** @var Album $libitem */
                $hide_genres       = (bool) AmpConfig::get('hide_genres');
                $show_played_times = (bool) AmpConfig::get('show_played_times');
                $results           = $this->guiFactory->createAlbumRowView(
                    $gatekeeper,
                    $this->browse,
                    $libitem,
                    $show_ratings,
                    $hide_genres,
                    $show_played_times,
                    true,
                    'cel_cover',
                    'cel_album',
                    'cel_artist',
                    'cel_tags',
                    'cel_counter'
                )->render();
                break;
            case 'album_disk_row':
                /** @var AlbumDisk $libitem */
                $hide_genres       = (bool) AmpConfig::get('hide_genres');
                $show_played_times = (bool) AmpConfig::get('show_played_times');
                $results           = $this->guiFactory->createAlbumDiskRowView(
                    $gatekeeper,
                    $this->browse,
                    $libitem,
                    $show_ratings,
                    $hide_genres,
                    $show_played_times,
                    true,
                    'cel_cover',
                    'cel_album',
                    'cel_artist',
                    'cel_tags',
                    'cel_counter'
                )->render();
                break;
            case 'artist_row':
                /** @var Artist $libitem */
                $results = (new ArtistRowView(
                    $libitem,
                    AmpConfig::get_web_path(),
                    'cel_cover',
                    'cel_artist',
                    'cel_time',
                    'cel_counter',
                    'cel_tags',
                    $this->browse->getId(),
                    false,
                    (bool) AmpConfig::get('hide_genres'),
                    $show_ratings,
                    (bool) AmpConfig::get('show_played_times'),
                    (bool) AmpConfig::get('directplay'),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                    (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) && (bool) AmpConfig::get('sociable'),
                    (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) && canEditArtist($libitem, $gatekeeper->getUserId()),
                    (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) && Catalog::can_remove($libitem)
                ))->render();
                break;
            case 'podcast_row':
                /** @var Podcast $libitem */
                $results = (new PodcastRowView(
                    $libitem,
                    AmpConfig::get_web_path(),
                    'cel_cover',
                    'cel_counter',
                    $show_ratings,
                    (bool) AmpConfig::get('show_played_times'),
                    (bool) AmpConfig::get('directplay'),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
                ))->render();
                break;
            case 'podcast_episode_row':
                /** @var Podcast_Episode $libitem */
                $results = (new PodcastEpisodeRowView(
                    $libitem,
                    AmpConfig::get_web_path(),
                    'cel_cover',
                    'cel_time',
                    'cel_counter',
                    $this->browse->getId(),
                    false,
                    true,
                    false,
                    $show_ratings,
                    (bool) AmpConfig::get('show_played_times'),
                    (bool) AmpConfig::get('directplay'),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                    Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
                    Catalog::can_remove($libitem)
                ))->render();
                break;
            case 'video_row':
                /** @var Video $libitem */
                $results = (new VideoRowView(
                    $libitem,
                    AmpConfig::get_web_path(),
                    'cel_cover',
                    'cel_counter',
                    'cel_tags',
                    $this->browse->getId(),
                    false,
                    (bool) AmpConfig::get('hide_genres'),
                    $show_ratings,
                    (bool) AmpConfig::get('show_played_times'),
                    (bool) AmpConfig::get('directplay'),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                    (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) && (bool) AmpConfig::get('sociable'),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) && (bool) AmpConfig::get('share'),
                    Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
                    Catalog::can_remove($libitem)
                ))->render();
                break;
            case 'live_stream_row':
                /** @var Live_Stream $libitem */
                $results = (new LiveStreamRowView(
                    $libitem,
                    'cel_cover',
                    $this->browse->getId(),
                    false,
                    $show_ratings,
                    (bool) AmpConfig::get('directplay'),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
                ))->render();
                break;
            case 'broadcast_row':
                /** @var Broadcast $libitem */
                $results = (new BroadcastRowView(
                    $libitem,
                    (bool) AmpConfig::get('directplay')
                ))->render();
                break;
            case 'label_row':
                /** @var Label $libitem */
                $results = (new LabelRowView(
                    AmpConfig::get_web_path(),
                    $libitem,
                    'cel_cover',
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                    (bool) AmpConfig::get('sociable'),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
                ))->render();
                break;
            case 'search_row':
                /** @var Search $libitem */
                $results = (new SearchRowView(
                    AmpConfig::get_web_path(),
                    $libitem,
                    (bool) AmpConfig::get('directplay'),
                    Stream_Playlist::check_autoplay_next(),
                    Stream_Playlist::check_autoplay_append(),
                    $show_ratings,
                    Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('search'),
                    $libitem->has_access()
                ))->render();
                break;
            case 'share_row':
                /** @var Share $libitem */
                $results = (new ShareRowView($libitem))->render();
                break;
            case 'song_preview_row':
                /** @var Song_Preview $libitem */
                $results = (new SongPreviewRowView(
                    $libitem,
                    (bool) AmpConfig::get('directplay'),
                    Stream_Playlist::check_autoplay_next(),
                    Stream_Playlist::check_autoplay_append()
                ))->render();
                break;
            case 'tag_row':
                /** @var Tag $libitem */
                $results = (new GenreRowView(
                    $this->ajaxUriRetriever->getAjaxUri(),
                    $libitem,
                    (bool) AmpConfig::get('allow_video'),
                    (bool) AmpConfig::get('directplay'),
                    Stream_Playlist::check_autoplay_next(),
                    Stream_Playlist::check_autoplay_append(),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
                ))->render();
                break;
            default:
                // pvmsg and wanted are not library items, so `loadItem()` refuses them before this point
                $results = '';
        }

        return $this->responseFactory->createResponse()
            ->withBody(
                $this->streamFactory->createStream((string) $results)
            );
    }
}
