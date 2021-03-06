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

namespace Ampache\Module\Playback\MediaUrlListGenerator;

use Ampache\Module\Playback\Localplay\LocalPlayControllerFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\LegacyLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * This calls the Localplay API to add the URLs and then start playback
 */
final class LocalplayMediaUrlGeneratorType extends AbstractMediaUrlListGeneratorType
{
    private LocalPlayControllerFactoryInterface $localPlayControllerFactory;

    private LoggerInterface $logger;

    public function __construct(
        LocalPlayControllerFactoryInterface $localPlayControllerFactory,
        LoggerInterface $logger
    ) {
        $this->localPlayControllerFactory = $localPlayControllerFactory;
        $this->logger                     = $logger;
    }

    public function generate(
        Stream_Playlist $playlist,
        ResponseInterface $response
    ): ResponseInterface {
        $localplay = $this->localPlayControllerFactory->create();
        $localplay->connect();

        /**
         * @todo inject RequestInterface as param in generate()
         */
        $append = $_REQUEST['append'];

        if (!$append) {
            $localplay->delete_all();
        }
        foreach ($playlist->urls as $url) {
            $localplay->add_url($url);
        }
        if (!$append) {
            // We don't have metadata on Stream_URL to know its kind
            // so we check the content to know if it is democratic
            if (count($playlist->urls) === 1) {
                $furl = $playlist->urls[0];
                if (strpos($furl->url, '&demo_id=1') !== false && $furl->time == -1) {
                    // If democratic, repeat the song to get the next voted one.
                    $this->logger->debug(
                        'Playing democratic on Localplay, enabling repeat...',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                    $localplay->repeat(true);
                }
            }
            $localplay->play();
        }

        return $response;
    }
}
