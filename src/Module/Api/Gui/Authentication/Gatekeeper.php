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

namespace Ampache\Module\Api\Gui\Authentication;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
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

    private PrivilegeCheckerInterface $privilegeChecker;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ServerRequestInterface $request,
        LoggerInterface $logger,
        PrivilegeCheckerInterface $privilegeChecker,
        ConfigContainerInterface $configContainer
    ) {
        $this->logger           = $logger;
        $this->request          = $request;
        $this->privilegeChecker = $privilegeChecker;
        $this->configContainer  = $configContainer;
    }

    public function getUser(): User
    {
        return User::get_from_username($this->getUserName());
    }

    public function sessionExists(): bool
    {
        return Session::exists('api', $this->getAuth());
    }

    public function extendSession(): void
    {
        Session::extend($this->getAuth());
    }

    public function endSession(): void
    {
        Session::destroy($this->getAuth());
    }

    public function getUserName(): string
    {
        return Session::username($this->getAuth());
    }

    public function getSessionExpiryDate(): int
    {
        return time() + $this->configContainer->getSessionLength() - 60;
    }

    public function getAuth(): string
    {
        if ($this->auth === null) {
            $auth = $this->request->getHeaderLine('Authorization');

            $matches = [];

            // Retrieve auth token from header
            preg_match('/Bearer ([0-9a-f].*)/', $auth, $matches);

            if ($matches !== []) {
                $token = $matches[1];
            } else {
                /**
                 * Fallback to legacy get parameter
                 *
                 * @todo Remove some day
                 */
                $token =
                    $this->request->getQueryParams()['auth'] ??
                    $this->request->getParsedBody()['auth'] ??
                    '';
            }

            $this->logger->info(
                sprintf('API session [%s]', $token),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            $this->auth = $token;
        }

        return $this->auth;
    }

    public function mayAccess(
        string $access_type,
        int $access_level
    ): bool {
        return $this->privilegeChecker->check($access_type, $access_level);
    }
}
