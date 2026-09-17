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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\ApiSessionMinter;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\QuickConnect\QuickConnectService;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Polls a QuickConnect pairing request started by `quickconnect_initiate`. Consuming the secret and
 * minting a session happen in the same call the client sees `authorized: true` in, rather than a second
 * round trip like the Jellyfin-compatible API's separate `/QuickConnect/Connect` and
 * `AuthenticateWithQuickConnect` calls — the native API has no reason to split the two.
 */
final class QuickConnectStatus8Method implements MethodInterface
{
    public const string ACTION = 'quickconnect_status';

    public function __construct(
        private readonly QuickConnectService $service,
        private readonly ApiSessionMinter $sessionMinter,
    ) {}

    /**
     * This is a public method: it can be called without being authenticated, since the caller has no session until the poll comes back authorized.
     *
     * secret = (string) the value `quickconnect_initiate` returned
     *
     * @param array{secret?: string, api_format: string} $input
     * @throws AccessDeniedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!$this->service->isEnabled()) {
            throw new AccessDeniedException('Enable: quickconnect_enable');
        }

        $secret = (string) ($input['secret'] ?? '');
        if ($secret === '') {
            throw new RequestParamMissingException('Bad Request: secret');
        }

        $row = $this->service->findBySecret($secret);
        if ($row === null) {
            throw new ResultEmptyException($secret, 'secret');
        }

        if (!(bool) $row['authorized']) {
            $response->getBody()->write(
                $output->keyedArray($apiVersion, ['authorized' => false])
            );

            return $response;
        }

        $result = $this->service->consume($secret);
        if (!$result['ok'] || $result['user'] === null) {
            throw new ResultEmptyException($secret, 'secret');
        }

        $token   = $this->sessionMinter->mint($result['user'], $apiVersion);
        $details = Api::server_details($token);

        $response->getBody()->write(
            $output->keyedArray($apiVersion, ['authorized' => true] + $details)
        );

        return $response;
    }
}
