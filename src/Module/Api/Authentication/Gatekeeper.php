<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;
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
    private ?string $auth = null;
    private LoggerInterface $logger;
    private ServerRequestInterface $request;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ServerRequestInterface $request,
        LoggerInterface $logger,
    ) {
        $this->userRepository = $userRepository;
        $this->logger         = $logger;
        $this->request        = $request;
    }

    public function extendSession(string $auth): void
    {
        Session::extend($auth, AccessTypeEnum::API->value);
    }

    public function getAuth(string $requestKey = 'auth'): string
    {
        if ($this->auth === null) {
            // Read headers separately: Authorization (preferred) and auth (alternate api-key header)
            $authorization = $this->request->getHeaderLine('Authorization');
            $authHeader    = $this->request->getHeaderLine('auth');

            $matches = [];
            $token   = '';

            // Retrieve auth token from Authorization: Bearer <token>
            if ($authorization !== '') {
                if (preg_match('/Bearer\s+(\S+)/i', $authorization, $matches)) {
                    $token = (string) $matches[1];
                    $this->logger->notice(
                        'API session using Bearer token',
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                } elseif (preg_match('/ApiKey\s+(\S+)/i', $authorization, $matches)) {
                    // Support Authorization: ApiKey <token>
                    $token = (string) $matches[1];
                    $this->logger->notice(
                        'API session using ApiKey in Authorization header',
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                }
            }

            if ($token === '' && $authHeader !== '') {
                // Accept API key passed directly in the 'auth' header (ApiKeyAuthHeader)
                $token = $authHeader;
                $this->logger->notice(
                    'API session using auth header',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }

            if ($token === '') {
                /**
                 * Fallback to legacy get/post parameter. (prefer POST array over GET)
                 * Remove some day when backwards compatability isn't a problem
                 */
                $post = ($this->request->getMethod() === 'POST')
                    ? (array) $this->request->getParsedBody()
                    : [];
                $query = array_merge($this->request->getQueryParams(), $post);
                $token = $query[$requestKey] ?? '';
                if ($token !== '') {
                    $this->logger->notice(
                        sprintf('API session [%s] (%s)', $token, $requestKey),
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                }
            }

            $this->auth = $token;
        }

        return $this->auth ?? '';
    }

    public function getUser(string $requestKey = 'auth'): ?User
    {
        return $this->userRepository->findByApiKey($this->getAuth($requestKey)) ?? $this->userRepository->findByUsername($this->request->getQueryParams()['user'] ?? '');
    }

    public function getUserName(string $requestKey = 'auth'): string
    {
        return (isset($this->request->getQueryParams()['user']))
            ? $this->request->getQueryParams()['user']
            : Session::username($this->getAuth($requestKey));
    }

    public function sessionExists(string $auth): bool
    {
        return Session::exists(AccessTypeEnum::API->value, $auth);
    }
}
