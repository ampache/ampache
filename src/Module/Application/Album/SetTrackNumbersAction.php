<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Album;

use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Song;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
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

    private UiInterface $ui;

    private LoggerInterface $logger;

    public function __construct(
        UiInterface $ui,
        LoggerInterface $logger
    ) {
        $this->ui     = $ui;
        $this->logger = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        $this->logger->debug(
            'Set track numbers called.',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER) === false) {
            throw new AccessDeniedException();
        }

        // Retrieving final song order from url
        foreach ($_GET as $key => $data) {
            $_GET[$key] = unhtmlentities(scrub_in((string) $data));

            $this->logger->debug(
                sprintf('%d=%s', $key, Core::get_get($key)),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }

        if (isset($_GET['order'])) {
            $songs = explode(';', Core::get_get('order'));
            $track = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT) ? ((filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT)) + 1) : 1;
            foreach ($songs as $song_id) {
                if ($song_id != '') {
                    Song::update_track($track, (int) $song_id);
                    ++$track;
                }
            }
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
