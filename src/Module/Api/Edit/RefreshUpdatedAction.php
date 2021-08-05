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

declare(strict_types=1);

namespace Ampache\Module\Api\Edit;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\database_object;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
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

    private UiInterface $ui;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        TalFactoryInterface $talFactory,
        GuiFactoryInterface $guiFactory,
        UiInterface $ui
    ) {
        parent::__construct($configContainer, $logger);
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->talFactory      = $talFactory;
        $this->guiFactory      = $guiFactory;
        $this->ui              = $ui;
    }

    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        database_object $libitem,
        int $object_id
    ): ?ResponseInterface {
        /**
         * @todo Every editable item type will need some sort of special handling here
         */
        switch ($object_type) {
            case 'song_row':
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
                    ->setContext('USING_RATINGS', User::is_registered() && (AmpConfig::get('ratings') || AmpConfig::get('userflags')))
                    ->setContext('SONG', $this->guiFactory->createSongViewAdapter($gatekeeper, $libitem))
                    ->setContext('CONFIG', $this->guiFactory->createConfigViewAdapter())
                    ->setContext('ARGUMENT_PARAM', $argument_param)
                    ->setContext('IS_TABLE_VIEW', true)
                    ->setContext('IS_SHOW_TRACK', (!empty($argument)))
                    ->setContext('IS_SHOW_LICENSE', $show_license)
                    ->setContext('IS_HIDE_ARTIST', $hide_artist)
                    ->setContext('IS_HIDE_ALBUM', $hide_album)
                    ->setContext('IS_HIDE_YEAR', $hide_year)
                    ->setContext('IS_HIDE_DRAG', $hide_drag)
                    ->setTemplate('song_row.xhtml')
                    ->render()
                );
                break;
            case 'playlist_row':
                $show_art     = AmpConfig::get('playlist_art');
                $show_ratings = User::is_registered() && (AmpConfig::get('ratings') || AmpConfig::get('userflags'));
                ob_start();

                $this->ui->show(
                    'show_' . $object_type . '.inc.php',
                    [
                        'libitem' => $libitem,
                        'object_type' => $object_type,
                        'object_id' => $object_id,
                        'show_art' => $show_art,
                        'show_ratings' => $show_ratings,
                    ]
                );

                $results = ob_get_contents();

                ob_end_clean();
                break;
            case 'album_row':
                $original_year    = AmpConfig::get('use_original_year');
                $show_ratings     = User::is_registered() && (AmpConfig::get('ratings') || AmpConfig::get('userflags'));
                $show_direct_play = AmpConfig::get('directplay');
                ob_start();

                $this->ui->show(
                    'show_' . $object_type . '.inc.php',
                    [
                        'libitem' => $libitem,
                        'object_type' => $object_type,
                        'object_id' => $object_id,
                        'original_year' => $original_year,
                        'show_ratings' => $show_ratings,
                        'show_direct_play' => $show_direct_play,
                        'show_playlist_add' => true,
                        'cel_cover' => 'cel_cover',
                        'cel_album' => 'cel_album',
                        'cel_artist' => 'cel_artist',
                        'cel_counter' => 'cel_counter',
                        'cel_tags' => 'cel_tags',
                    ]
                );

                $results = ob_get_contents();

                ob_end_clean();
                break;
            case 'artist_row':
                $show_ratings      = User::is_registered() && (AmpConfig::get('ratings') || AmpConfig::get('userflags'));
                $show_direct_play  = AmpConfig::get('directplay');
                ob_start();

                $this->ui->show(
                    'show_' . $object_type . '.inc.php',
                    [
                        'libitem' => $libitem,
                        'object_type' => $object_type,
                        'object_id' => $object_id,
                        'show_ratings' => $show_ratings,
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
            default:
                ob_start();

                $this->ui->show(
                    'show_' . $object_type . '.inc.php',
                    [
                        'libitem' => $libitem,
                        'object_type' => $object_type,
                        'object_id' => $object_id,
                    ]
                );

                $results = ob_get_contents();

                ob_end_clean();
    }

        return $this->responseFactory->createResponse()
            ->withBody(
                $this->streamFactory->createStream($results)
            );
    }
}
