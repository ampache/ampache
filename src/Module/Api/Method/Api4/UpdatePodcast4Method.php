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

use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

final class UpdatePodcast4Method implements MethodInterface
{
    public const string ACTION = 'update_podcast';

    public function __construct(
        private PodcastRepositoryInterface $podcastRepository,
        private PodcastSyncerInterface $podcastSyncer,
    ) {}

    /**
     * update_podcast
     * MINIMUM_API_VERSION=420000
     *
     * Sync and download new podcast episodes
     *
     * filter = (string) UID of podcast
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type: string,
     *     overwrite: int,
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
        $input['filter'] = $input['filter'] ?? $input['id'] ?? null;
        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return $response;
        }
        if (!Api4::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->id, 'update_podcast', $input['api_format'])) {
            return $response;
        }
        $object_id = (int) $input['filter'];
        $podcast   = $this->podcastRepository->findById($object_id);

        if ($podcast !== null) {
            if ($this->podcastSyncer->sync($podcast, true)) {
                Api4::message('success', 'Synced episodes for podcast: ' . $object_id, null, $input['api_format']);
                Session::extend($input['auth'], AccessTypeEnum::API->value);
            } else {
                Api4::message('error', 'Failed to sync episodes for podcast: ' . $object_id, '400', $input['api_format']);
            }
        } else {
            Api4::message('error', 'podcast ' . $object_id . ' was not found', '404', $input['api_format']);
        }

        return $response;
    }
}
