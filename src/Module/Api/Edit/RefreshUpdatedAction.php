<?php

declare(strict_types=1);

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

namespace Ampache\Module\Api\Edit;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Browse;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class RefreshUpdatedAction extends AbstractEditAction
{
    public const REQUEST_KEY = 'refresh_updated';

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private TalFactoryInterface $talFactory;

    private GuiFactoryInterface $guiFactory;

    private Browse $browse;

    private UiInterface $ui;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        TalFactoryInterface $talFactory,
        GuiFactoryInterface $guiFactory,
        Browse $browse,
        UiInterface $ui
    ) {
        parent::__construct($configContainer, $logger);
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->talFactory      = $talFactory;
        $this->guiFactory      = $guiFactory;
        $this->browse          = $browse;
        $this->ui              = $ui;
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
     * * license_row
     * * shout_row
     * * user_row
     */
    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        library_item $libitem,
        int $object_id
    ): ?ResponseInterface {
        $show_ratings = User::is_registered() && (AmpConfig::get('ratings'));
        /**
         * @todo Every editable item type will need some sort of special handling here
         */
        switch ($object_type) {
            case 'song_row':
                /** @var Song $libitem */
                $hide_genres    = AmpConfig::get('hide_genres');
                $show_license   = AmpConfig::get('licensing') && AmpConfig::get('show_license');
                $argument_param = '&hide=' . Core::get_request('hide');
                $argument       = explode(',', Core::get_request('hide'));
                $hide_artist    = in_array('cel_artist', $argument);
                $hide_album     = in_array('cel_album', $argument);
                $hide_year      = in_array('cel_year', $argument);
                $hide_drag      = in_array('cel_drag', $argument);
                $results        = preg_replace(
                    '/<\/?html(.|\s)*?>/',
                    '',
                    $this->talFactory->createTalView()
                        ->setContext('BROWSE_ARGUMENT', '')
                        ->setContext('USER_IS_REGISTERED', true)
                        ->setContext('USING_RATINGS', $show_ratings)
                        ->setContext('SONG', $this->guiFactory->createSongViewAdapter($gatekeeper, $libitem))
                        ->setContext('CONFIG', $this->guiFactory->createConfigViewAdapter())
                        ->setContext('ARGUMENT_PARAM', $argument_param)
                        ->setContext('IS_TABLE_VIEW', true)
                        ->setContext('IS_SHOW_TRACK', (!empty($argument)))
                        ->setContext('IS_SHOW_LICENSE', $show_license)
                        ->setContext('IS_HIDE_GENRE', $hide_genres)
                        ->setContext('IS_HIDE_ARTIST', $hide_artist)
                        ->setContext('IS_HIDE_ALBUM', $hide_album)
                        ->setContext('IS_HIDE_YEAR', $hide_year)
                        ->setContext('IS_HIDE_DRAG', $hide_drag)
                        ->setTemplate('song_row.xhtml')
                        ->render()
                );
                break;
            case 'playlist_row':
                /** @var Playlist $libitem */
                $show_art = AmpConfig::get('playlist_art');
                $results  = preg_replace(
                    '/<\/?html(.|\s)*?>/',
                    '',
                    $this->talFactory->createTalView()
                        ->setContext('USING_RATINGS', User::is_registered() && (AmpConfig::get('ratings')))
                        ->setContext('PLAYLIST', $this->guiFactory->createPlaylistViewAdapter($gatekeeper, $libitem))
                        ->setContext('CONFIG', $this->guiFactory->createConfigViewAdapter())
                        ->setContext('IS_SHOW_ART', $show_art)
                        ->setContext('IS_SHOW_PLAYLIST_ADD', true)
                        ->setContext('CLASS_COVER', 'cel_cover')
                        ->setTemplate('playlist_row.xhtml')
                        ->render()
                );
                break;
            case 'album_row':
                /** @var Album $libitem */
                $hide_genres       = AmpConfig::get('hide_genres');
                $show_played_times = AmpConfig::get('show_played_times');
                $results           = preg_replace(
                    '/<\/?html(.|\s)*?>/',
                    '',
                    $this->talFactory->createTalView()
                        ->setContext('USER_IS_REGISTERED', User::is_registered())
                        ->setContext('USING_RATINGS', $show_ratings)
                        ->setContext('ALBUM', $this->guiFactory->createAlbumViewAdapter($gatekeeper, $this->browse, $libitem))
                        ->setContext('CONFIG', $this->guiFactory->createConfigViewAdapter())
                        ->setContext('IS_TABLE_VIEW', true)
                        ->setContext('IS_HIDE_GENRE', $hide_genres)
                        ->setContext('IS_SHOW_PLAYED_TIMES', $show_played_times)
                        ->setContext('IS_SHOW_PLAYLIST_ADD', true)
                        ->setContext('CLASS_COVER', 'cel_cover')
                        ->setContext('CLASS_ALBUM', 'cel_album')
                        ->setContext('CLASS_ARTIST', 'cel_artist')
                        ->setContext('CLASS_TAGS', 'cel_tags')
                        ->setContext('CLASS_COUNTER', 'cel_counter')
                        ->setTemplate('album_row.xhtml')
                        ->render()
                );
                break;
            case 'album_disk_row':
                /** @var AlbumDisk $libitem */
                $hide_genres       = AmpConfig::get('hide_genres');
                $show_played_times = AmpConfig::get('show_played_times');
                $results           = preg_replace(
                    '/<\/?html(.|\s)*?>/',
                    '',
                    $this->talFactory->createTalView()
                        ->setContext('USER_IS_REGISTERED', User::is_registered())
                        ->setContext('USING_RATINGS', $show_ratings)
                        ->setContext('ALBUMDISK', $this->guiFactory->createAlbumDiskViewAdapter($gatekeeper, $this->browse, $libitem))
                        ->setContext('CONFIG', $this->guiFactory->createConfigViewAdapter())
                        ->setContext('IS_TABLE_VIEW', true)
                        ->setContext('IS_HIDE_GENRE', $hide_genres)
                        ->setContext('IS_SHOW_PLAYED_TIMES', $show_played_times)
                        ->setContext('IS_SHOW_PLAYLIST_ADD', true)
                        ->setContext('CLASS_COVER', 'cel_cover')
                        ->setContext('CLASS_ALBUM', 'cel_album')
                        ->setContext('CLASS_ARTIST', 'cel_artist')
                        ->setContext('CLASS_TAGS', 'cel_tags')
                        ->setContext('CLASS_COUNTER', 'cel_counter')
                        ->setTemplate('album_disk_row.xhtml')
                        ->render()
                );
                break;
            case 'artist_row':
                /** @var Artist $libitem */
                $hide_genres      = AmpConfig::get('hide_genres');
                $show_direct_play = AmpConfig::get('directplay');
                ob_start();

                $this->ui->show(
                    'show_' . $object_type . '.inc.php',
                    [
                        'libitem' => $libitem,
                        'is_table' => true,
                        'object_type' => $object_type,
                        'object_id' => $object_id,
                        'show_ratings' => $show_ratings,
                        'hide_genres' => $hide_genres,
                        'show_direct_play' => $show_direct_play,
                        'cel_cover' => 'cel_cover',
                        'cel_artist' => 'cel_artist',
                        'cel_time' => 'cel_time',
                        'cel_counter' => 'cel_counter',
                        'cel_tags' => 'cel_tags',
                    ]
                );

                $results = ob_get_contents();

                ob_end_clean();
                break;
            case 'podcast_row':
                /** @var Podcast $libitem */
                ob_start();

                $this->ui->show(
                    'show_' . $object_type . '.inc.php',
                    [
                        'libitem' => $libitem,
                        'is_mashup' => false,
                        'is_table' => true,
                        'object_type' => $object_type,
                        'object_id' => $object_id,
                        'show_ratings' => $show_ratings,
                        'cel_cover' => 'cel_cover',
                        'cel_time' => 'cel_time',
                        'cel_counter' => 'cel_counter',
                    ]
                );

                $results = ob_get_contents();

                ob_end_clean();
                break;
            case 'podcast_episode_row':
                /** @var Podcast_Episode $libitem */
                ob_start();

                $this->ui->show(
                    'show_' . $object_type . '.inc.php',
                    [
                        'libitem' => $libitem,
                        'is_mashup' => false,
                        'is_table' => true,
                        'object_type' => $object_type,
                        'object_id' => $object_id,
                        'show_ratings' => $show_ratings,
                        'cel_cover' => 'cel_cover',
                        'cel_counter' => 'cel_counter',
                        'cel_time' => 'cel_time',
                    ]
                );

                $results = ob_get_contents();

                ob_end_clean();
                break;
            case 'video_row':
                /** @var Video $libitem */
                $hide_genres = AmpConfig::get('hide_genres');
                ob_start();

                $this->ui->show(
                    'show_' . $object_type . '.inc.php',
                    [
                        'libitem' => $libitem,
                        'is_table' => true,
                        'object_type' => $object_type,
                        'object_id' => $object_id,
                        'show_ratings' => $show_ratings,
                        'hide_genres' => $hide_genres,
                        'cel_cover' => 'cel_cover',
                        'cel_counter' => 'cel_counter',
                        'cel_tags' => 'cel_tags',
                    ]
                );

                $results = ob_get_contents();

                ob_end_clean();
                break;
            case 'live_stream_row':
                ob_start();

                $this->ui->show(
                    'show_' . $object_type . '.inc.php',
                    [
                        'libitem' => $libitem,
                        'is_table' => true,
                        'object_type' => $object_type,
                        'object_id' => $object_id,
                        'show_ratings' => $show_ratings,
                        'cel_cover' => 'cel_cover',
                    ]
                );

                $results = ob_get_contents();

                ob_end_clean();
                break;
            default:
                /*
                 * Templates that don't need anything special
                 *
                 * broadcast_row
                 * label_row
                 * live_stream_row
                 * pvmsg_row
                 * search_row
                 * share_row
                 * song_preview_row
                 * tag_row
                 * wanted_album_row
                 */
                ob_start();

                $this->ui->show(
                    'show_' . $object_type . '.inc.php',
                    [
                        'libitem' => $libitem,
                        'is_table' => true,
                        'object_type' => $object_type,
                        'object_id' => $object_id,
                        'show_ratings' => $show_ratings,
                    ]
                );

                $results = ob_get_contents();

                ob_end_clean();
        }

        return $this->responseFactory->createResponse()
            ->withBody(
                $this->streamFactory->createStream((string)$results)
            );
    }
}
