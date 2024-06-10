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

namespace Ampache\Module\Application\Stream;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ApplicationException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractStreamAction implements ApplicationActionInterface
{
    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    protected function __construct(
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer
    ) {
        $this->logger          = $logger;
        $this->configContainer = $configContainer;
    }

    /**
     * @throws ApplicationException
     */
    protected function preCheck(
        GuiGatekeeperInterface $gatekeeper
    ): bool {
        if (!defined('NO_SESSION')) {
            /* If we are running a demo, quick while you still can! */
            if (
                $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true ||
                (
                    $this->configContainer->isAuthenticationEnabled() &&
                    $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) === false
                )
            ) {
                throw new AccessDeniedException();
            }
        }

        return true;
    }

    /**
     * @throws ApplicationException
     */
    protected function stream(
        array $mediaIds,
        array $urls,
        string $streamType = ''
    ): ?ResponseInterface {
        if ($streamType == 'stream') {
            $streamType = $this->configContainer->get(ConfigurationKeyEnum::PLAYLIST_TYPE);
        }

        $this->logger->debug(
            'Stream Type: ' . $streamType . ' Media IDs: ' . json_encode($mediaIds),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );
        if ($mediaIds !== [] || $urls !== []) {
            if (!defined('NO_SESSION') && $streamType != 'democratic') {
                if (!User::stream_control($mediaIds)) {
                    $this->logger->warning(
                        'Stream control failed for user ' . Core::get_global('user')->username,
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                    throw new AccessDeniedException();
                }
            }

            if (Core::get_global('user')->id > -1) {
                Session::update_username(Stream::get_session(), Core::get_global('user')->username);
            }

            $playlist = new Stream_Playlist();

            // don't do this if nothing is there
            if ($mediaIds !== []) {
                $this->logger->debug(
                    sprintf('Stream Type: %s Media Count: %d', $streamType, count($mediaIds)),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                $playlist->add($mediaIds);
            }
            if (!empty($urls)) {
                $this->logger->debug(
                    sprintf('Stream Type: %s Loading URL: %s', $streamType, $urls[0]),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                $playlist->add_urls($urls);
            }

            // Depending on the stream type, will either generate a redirect or actually do the streaming.
            $playlist->generate_playlist($streamType);
        } else {
            $this->logger->debug(
                'No item. Ignoring...',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }

        return null;
    }
}
