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

declare(strict_types=0);

namespace Ampache\Module\Api\Authentication;

use Ampache\Repository\Model\User;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides methods to access authentication related data
 *
 * There is only XUL.
 */
final class Gatekeeper implements GatekeeperInterface
{
    private ServerRequestInterface $request;

    private LoggerInterface $logger;

    private ?string $auth = null;

    public function __construct(
        ServerRequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->logger  = $logger;
        $this->request = $request;
    }

    public function getUser(): ?User
    {
        return (isset($this->request->getQueryParams()['user']))
            ? User::get_from_username($this->request->getQueryParams()['user'])
            : User::get_from_apikey($this->getAuth());
    }

    public function sessionExists(): bool
    {
        return Session::exists('api', $this->getAuth());
    }

    public function extendSession(): void
    {
        Session::extend($this->getAuth());
    }

    public function getUserName(): string
    {
        return (isset($this->request->getQueryParams()['user']))
            ? $this->request->getQueryParams()['user']
            : Session::username($this->getAuth());
    }

    public function getAuth(): string
    {
        if ($this->auth === null) {
            $auth = $this->request->getHeaderLine('Authorization');

            $matches = [];

            // Retrieve auth token from header
            preg_match('/Bearer: ([0-9a-f].*)/', $auth, $matches);

            if ($matches !== []) {
                $token = $matches[1];
            } else {
                /**
                 * Fallback to legacy get parameter
                 * Remove some day when backwards compatability isn't a problem
                 */
                $token = $this->request->getQueryParams()['auth'] ?? '';
            }

            $this->logger->notice(
                sprintf('API session [%s]', $token),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            $this->auth = $token;
        }

        return $this->auth;
    }
}
