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

namespace Ampache\Module\User\Authorization;

use Ampache\Repository\Model\User;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\UserRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

final class UserKeyGenerator implements UserKeyGeneratorInterface
{
    private UserRepositoryInterface $userRepository;

    private LoggerInterface $logger;

    public function __construct(
        UserRepositoryInterface $userRepository,
        LoggerInterface $logger
    ) {
        $this->userRepository = $userRepository;
        $this->logger         = $logger;
    }

    /**
     * Generates and saves a new API key for the given user
     */
    public function generateApikey(
        User $user
    ): void {
        $userId = $user->getId();
        $apikey = hash(
            'md5',
            time() . $user->username . $this->userRepository->retrievePasswordFromUser($userId)
        );

        $this->userRepository->updateApiKey($userId, $apikey);

        $this->logger->notice(
            sprintf('Updating apikey for %d', $userId),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );
    }

    /**
     * Generates and saves a new RSS token for the given user
     */
    public function generateRssToken(
        User $user
    ): void {
        try {
            $rsstoken = bin2hex(random_bytes(32));
            $userId   = $user->getId();

            $this->userRepository->updateRssToken(
                $userId,
                $rsstoken
            );

            $this->logger->notice(
                sprintf('Updating rsstoken for %d', $userId),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        } catch (Exception $error) {
            $this->logger->error(
                sprintf('Could not generate random_bytes: %s', $error->getMessage()),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }
    }

    /**
     * Generates and saves a new Stream token for the given user
     */
    public function generateStreamToken(
        User $user
    ): void {
        try {
            $streamtoken = bin2hex(random_bytes(20));
            $userId      = $user->getId();
            $userName    = $user->username;

            $this->userRepository->updateStreamToken(
                $userId,
                $userName,
                $streamtoken
            );

            $this->logger->notice(
                sprintf('Updating streamtoken for %d', $userId),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        } catch (Exception $error) {
            $this->logger->error(
                sprintf('Could not generate random_bytes: %s', $error->getMessage()),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }
    }
}
