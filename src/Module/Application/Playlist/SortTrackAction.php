<?php
/*
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

namespace Ampache\Module\Application\Playlist;

use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SortTrackAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'sort_tracks';

    private RequestParserInterface $requestParser;

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    public function __construct(
        RequestParserInterface $requestParser,
        ModelFactoryInterface $modelFactory,
        UiInterface $ui
    ) {
        $this->requestParser = $requestParser;
        $this->modelFactory  = $modelFactory;
        $this->ui            = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $playlist_id = (int)$this->requestParser->getFromRequest('playlist_id');
        $playlist    = $this->modelFactory->createPlaylist($playlist_id);
        if (!$playlist->has_access()) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        /* Sort the tracks */
        $playlist->sort_tracks();
        $object_ids = $playlist->get_items();
        $this->ui->show(
            'show_playlist.inc.php',
            [
                'playlist' => $playlist,
                'object_ids' => $object_ids
            ]
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
