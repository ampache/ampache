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

namespace Ampache\Module\Api\Authentication;

use Ampache\Module\Api\Api;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

final class Handshake implements HandshakeInterface
{
    private const TIMESTAMP_GRACE_PERIOD = 1800;

    private UserRepositoryInterface $userRepository;

    private NetworkCheckerInterface $networkChecker;

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        UserRepositoryInterface $userRepository,
        NetworkCheckerInterface $networkChecker,
        LoggerInterface $logger,
        ModelFactoryInterface $modelFactory
    ) {
        $this->userRepository = $userRepository;
        $this->networkChecker = $networkChecker;
        $this->logger         = $logger;
        $this->modelFactory   = $modelFactory;
    }

    /**
     * Performs the actual login for api users
     *
     * @throws Exception\HandshakeException
     */
    public function handshake(
        string $username,
        string $passphrase,
        int $timestamp,
        string $version,
        string $userIp
    ): User {
        // set the version to the old string for old api clients
        Api::$version = ((int) $version >= 350001) ? '500000' : Api::$version;

        $this->logger->debug(
            sprintf('Handshake Attempt, IP:%s User:%s Version:%s', $userIp, $username, $version),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        // Version check shouldn't be soo restrictive... only check with initial version to not break clients compatibility
        if ((int) ($version) < Api::$auth_version && ((int) $version[0]) !== 5) {
            $this->logger->critical(
                'Login Failed: Version too old',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            throw new Exception\HandshakeException(T_('Login failed, API version is too old'));
        }

        $userId = null;
        // Grab the correct userid
        if (!$username) {
            $client = $this->userRepository->findByApiKey($passphrase);
            if ($client !== null) {
                $userId = $client->getId();
            }
        } else {
            $userId = $this->userRepository->findByUsername($username);
            if ($userId !== null) {
                $client = $this->modelFactory->createUser($userId);
            }
        }

        $this->logger->critical(
            sprintf('Login Attempt, IP:%s Time: %s User:%s (%s) Auth:%s', $userIp, $timestamp, $username, $userId, $passphrase),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        if (
            $userId !== null &&
            $this->networkChecker->check(AccessLevelEnum::TYPE_API, $userId, AccessLevelEnum::LEVEL_GUEST)
        ) {
            // Authentication with user/password, we still need to check the password
            if ($username) {
                $currentTime = time();
                // If the timestamp isn't within 30 minutes sucks to be them
                if (
                    $timestamp < ($currentTime - static::TIMESTAMP_GRACE_PERIOD) ||
                    $timestamp > ($currentTime + static::TIMESTAMP_GRACE_PERIOD)
                ) {
                    $this->logger->critical(
                        sprintf('Login failed, timestamp is out of range %d/%d', $timestamp, $currentTime),
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );

                    throw new Exception\HandshakeException(T_('Login failed, timestamp is out of range'));
                }

                // Now we're sure that there is an ACL line that matches
                // this user or ALL USERS, pull the user's password and
                // then see what we come out with
                $realpwd = $this->userRepository->retrievePasswordFromUser($userId);

                if (!$realpwd) {
                    $this->logger->critical(
                        sprintf('Unable to find user with userid of %d', $userId),
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );

                    throw new Exception\HandshakeException(T_('Login failed, timestamp is out of range'));
                }

                $sha1pass = hash('sha256', $timestamp . $realpwd);

                if ($sha1pass !== $passphrase) {
                    $client = null;
                }
            }

            if ($client) {
                $this->logger->critical(
                    'Login Success, passphrase matched',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                return $client;
            }
        }

        $this->logger->critical(
            'Login Failed, unable to match passphrase',
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        throw new Exception\HandshakeException(T_('Incorrect username or password'));
    }
}
