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

namespace Ampache\Module\Application\MiniPlayer;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Playback\MiniPlayerView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\EnvironmentInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Show the mini player; a standalone page with the home plugins and the web player only.
 */
final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private AjaxUriRetrieverInterface $ajaxUriRetriever,
        private CollectionRepositoryInterface $collectionRepository,
        private EnvironmentInterface $environment,
        private LibraryItemLoaderInterface $libraryItemLoader,
        private PlaylistLoaderInterface $playlistLoader,
        private ZipHandlerInterface $zipHandler,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // The mini header carries the play type switcher (show_playtype_switch.inc.php) so a user
        // locked into this page can move between web player, stream, localplay and democratic
        // without reaching their preferences. The page ships everything each type needs: #webplayer,
        // the util_iframe for stream/democratic and the rightbar for localplay. Don't force a type
        // here or the switcher can't stick.
        echo new MiniPlayerView(
            AmpConfig::get_web_path(),
            $this->environment,
            $this->ajaxUriRetriever,
            $this->collectionRepository,
            $this->libraryItemLoader,
            $this->playlistLoader,
            $this->zipHandler
        )->render();

        return null;
    }
}
