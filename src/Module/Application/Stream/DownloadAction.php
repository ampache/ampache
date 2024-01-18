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

        if (array_key_exists('song_id', $_REQUEST)) {
            $mediaIds[] = array(
                'object_type' => 'song',
                'object_id' => scrub_in((string) $_REQUEST['song_id'])
            );
        } elseif (array_key_exists('video_id', $_REQUEST)) {
            $mediaIds[] = array(
                'object_type' => 'video',
                'object_id' => scrub_in((string) $_REQUEST['video_id'])
            );
        } elseif (array_key_exists('podcast_episode_id', $_REQUEST)) {
            $mediaIds[] = array(
                'object_type' => 'podcast_episode',
                'object_id' => scrub_in((string) $_REQUEST['podcast_episode_id'])
            );
        } elseif (array_key_exists('share_id', $_REQUEST)) {
            $mediaIds[] = array(
                'object_type' => 'share',
                'object_id' => scrub_in((string) $_REQUEST['share_id'])
            );
        }
        // add the missing request parts
        if (array_key_exists('client', $_REQUEST)) {
            $mediaIds[0]['client'] = scrub_in((string) $_REQUEST['client']);
        }
        if (array_key_exists('player', $_REQUEST)) {
            $mediaIds[0]['player'] = scrub_in((string) $_REQUEST['player']);
        }
        if (array_key_exists('cache', $_REQUEST)) {
            $mediaIds[0]['cache'] = scrub_in((string) $_REQUEST['cache']);
        }
        if (array_key_exists('format', $_REQUEST)) {
            $mediaIds[0]['format'] = scrub_in((string) $_REQUEST['format']);
        } else {
            $mediaIds[0]['format'] = 'raw';
        }
        if (array_key_exists('transcode_to', $_REQUEST)) {
            $mediaIds[0]['transcode_to'] = scrub_in((string) $_REQUEST['transcode_to']);
        }

        return $this->stream(
            $mediaIds,
            [],
            'download'
        );
    }
}
