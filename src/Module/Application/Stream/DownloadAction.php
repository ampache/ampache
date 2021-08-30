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

declare(strict_types=0);

namespace Ampache\Module\Application\Stream;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class DownloadAction extends AbstractStreamAction
{
    public const REQUEST_KEY = 'download';

    public function __construct(
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct($logger, $configContainer);
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->preCheck($gatekeeper) === false) {
            return null;
        }

        $mediaIds = [];

        if (isset($_REQUEST['song_id'])) {
            $mediaIds[] = array(
                'object_type' => 'song',
                'object_id' => scrub_in($_REQUEST['song_id'])
            );
        } elseif (isset($_REQUEST['video_id'])) {
            $mediaIds[] = array(
                'object_type' => 'video',
                'object_id' => scrub_in($_REQUEST['video_id'])
            );
        } elseif (isset($_REQUEST['podcast_episode_id'])) {
            $mediaIds[] = array(
                'object_type' => 'podcast_episode',
                'object_id' => scrub_in($_REQUEST['podcast_episode_id'])
            );
        }


        return $this->stream(
            $mediaIds,
            [],
            'download'
        );
    }
}
