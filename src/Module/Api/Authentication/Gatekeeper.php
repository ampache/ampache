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

namespace Ampache\Module\Api\Authentication;

use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides methods to access authentication related data
 *
 * There is only XUL.
 */
final class Gatekeeper implements GatekeeperInterface
{
    private UserRepositoryInterface $userRepository;

    private ServerRequestInterface $request;

    private LoggerInterface $logger;

    private ?string $auth = null;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ServerRequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->userRepository = $userRepository;
        $this->logger         = $logger;
        $this->request        = $request;
    }

    public function getUser(string $requestKey = 'auth'): ?User
    {
        return $this->userRepository->findByApiKey($this->getAuth($requestKey)) ?? $this->userRepository->findByUsername($this->request->getQueryParams()['user'] ?? '');
    }

    public function sessionExists(string $auth): bool
    {
        return Session::exists(AccessTypeEnum::API->value, $auth);
    }

    public function extendSession(string $auth): void
    {
        Session::extend($auth, AccessTypeEnum::API->value);
    }

    public function getUserName(string $requestKey = 'auth'): string
    {
        return (isset($this->request->getQueryParams()['user']))
            ? $this->request->getQueryParams()['user']
            : Session::username($this->getAuth($requestKey));
    }

    public function getAuth(string $requestKey = 'auth'): string
    {
        if ($this->auth === null) {
            $auth = $this->request->getHeaderLine('Authorization');

            $matches = [];

            // Retrieve auth token from header
            preg_match('/Bearer ([0-9a-f].*)/', $auth, $matches);

            if ($matches !== []) {
                $token = (string)$matches[1];
                $this->logger->notice(
                    'API session using Bearer token',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            } else {
                /**
                 * Fallback to legacy get parameter
                 * Remove some day when backwards compatability isn't a problem
                 */
                $token = (string)($this->request->getQueryParams()[$requestKey] ?? '');
                if ($token !== '') {
                    $this->logger->notice(
                        sprintf('API session [%s] (%s)', $token, $requestKey),
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                }
            }


            $this->auth = $token;
        }

        return $this->auth;
    }
}
