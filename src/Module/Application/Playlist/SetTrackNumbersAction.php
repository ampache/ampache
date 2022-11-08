<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class SetTrackNumbersAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'set_track_numbers';

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    private LoggerInterface $logger;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        UiInterface $ui,
        LoggerInterface $logger
    ) {
        $this->modelFactory = $modelFactory;
        $this->ui           = $ui;
        $this->logger       = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $playlist = $this->modelFactory->createPlaylist((int) $_REQUEST['playlist_id']);
        /* Make sure they have permission */
        if (!$playlist->has_access()) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        // Retrieving final song order from url
        foreach ($_GET as $key => $data) {
            $_GET[$key] = unhtmlentities((string) scrub_in($data));

            $this->logger->debug(
                $key . '=' . Core::get_get($key),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }

        if (array_key_exists('order', $_GET)) {
            $songs = explode(";", $_GET['order']);
            $track = ((int)$_GET['offset'] ?? 0) + 1;
            if ($track < 1) {
                $track = 1;
            }
            foreach ($songs as $song_id) {
                if ($song_id != '') {
                    $playlist->update_track_number((int) $song_id, $track);
                    ++$track;
                }
            }
        } else {
            $playlist->regenerate_track_numbers();
        }
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
