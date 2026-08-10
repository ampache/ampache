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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class LastShouts4Method implements MethodInterface
{
    public const string ACTION = 'last_shouts';

    public function __construct(
        private ShoutRepositoryInterface $shoutRepository,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * last_shouts
     * MINIMUM_API_VERSION=380001
     *
     * This get the latest posted shouts
     *
     * username = (string) $username //optional
     * limit = (integer) $limit Default: 10 (popular_threshold) //optional
     *
     * @param array{
     *     username?: string,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!AmpConfig::get('sociable')) {
            Api4::message('error', 'Access Denied: social features are not enabled.', '400', $input['api_format']);

            return $response;
        }
        if (!Api4::check_parameter($input, ['username'], self::ACTION)) {
            return $response;
        }
        unset($user);
        $limit = (int) ($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = AmpConfig::get_int('popular_threshold', 10);
        }
        if (!empty($input['username'])) {
            $username = $input['username'];
        } else {
            $username = null;
        }

        $results = $this->shoutRepository->getTop($limit, $username);

        ob_end_clean();

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->shouts($apiVersion, iterator_to_array($results))
            )
        );
    }
}
