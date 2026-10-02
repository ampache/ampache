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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\QuickConnect\QuickConnectService;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Starts a QuickConnect pairing request for the native API: a client with no session yet asks for a short
 * code, shows it to the person signing in, then polls `quickconnect_status` with the returned `secret`
 * until it comes back authorized. This is the same pairing service, and the same `quickconnect_enable`
 * preference, the Jellyfin-compatible API and the web preferences approval page already use.
 */
final class QuickConnectInitiate8Method implements MethodInterface
{
    public const string ACTION = 'quickconnect_initiate';

    public function __construct(private readonly QuickConnectService $service) {}

    /**
     * This is a public method: it can be called without being authenticated, since its whole purpose is
     * getting a device its first session.
     *
     * device_id   = (string) an id the caller makes up and keeps, so its own initiate rate limit applies //optional
     * device_name = (string) shown to the approving user alongside the code //optional
     * client      = (string) the app name, shown to the approving user //optional
     * version     = (string) the app version, shown to the approving user //optional
     *
     * @param array{
     *     device_id?: string,
     *     device_name?: string,
     *     client?: string,
     *     version?: string,
     *     api_format: string,
     * } $input
     * @throws AccessDeniedException
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

        $row = $this->service->initiate(
            (string) ($input['device_id'] ?? ''),
            (string) ($input['device_name'] ?? ''),
            (string) ($input['client'] ?? ''),
            (string) ($input['version'] ?? ''),
        );
        if ($row === null) {
            throw new AccessDeniedException('Too many requests');
        }

        $response->getBody()->write(
            $output->keyedArray($apiVersion, $row)
        );

        return $response;
    }
}
