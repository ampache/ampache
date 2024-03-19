<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Playlist;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private UiInterface $ui;

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;
    public function __construct(
        UiInterface $ui,
        LoggerInterface $logger,
        ModelFactoryInterface $modelFactory
    ) {
        $this->ui           = $ui;
        $this->logger       = $logger;
        $this->modelFactory = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $playlist = $this->modelFactory->createPlaylist(
            (int)($_REQUEST['playlist_id'] ?? 0)
        );
        $this->ui->showHeader();

        if ($playlist->isNew() || (!$playlist->has_access() and $playlist->type === 'private')) {
            $this->logger->warning(
                'Requested a playlist that does not exist',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            $playlist->format();
            $object_ids = $playlist->get_items();
            $this->ui->show(
                'show_playlist.inc.php',
                [
                    'playlist' => $playlist,
                    'object_ids' => $object_ids
                ]
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
