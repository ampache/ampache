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
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns a single podcast.
 */
final class Podcast4Method implements MethodInterface
{
    public const string ACTION = 'podcast';

    public function __construct(
        private PodcastRepositoryInterface $podcastRepository,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * @param array<string, mixed> $input
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
        if (!AmpConfig::get('podcast')) {
            Api4::message('error', 'Access Denied: podcast features are not enabled.', '400', $input['api_format']);

            return $response;
        }

        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return $response;
        }

        $object_id = (int) ($input['filter'] ?? 0);
        if ($this->podcastRepository->findById($object_id) === null) {
            Api4::message('error', 'podcast ' . $object_id . ' was not found', '404', $input['api_format']);

            return $response;
        }

        $episodes = (array_key_exists('include', $input) && $input['include'] == 'episodes');

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->podcasts($apiVersion, [$object_id], $user, $input['auth'], $episodes)
            )
        );
    }
}
