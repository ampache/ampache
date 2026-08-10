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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Syncs and downloads new podcast episodes
 */
final class UpdatePodcastMethod implements MethodInterface
{
    public const string ACTION = 'update_podcast';

    public const string REST_ACTION = 'sync';

    private PodcastRepositoryInterface $podcastRepository;
    private PodcastSyncerInterface $podcastSyncer;
    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PodcastRepositoryInterface $podcastRepository,
        PodcastSyncerInterface $podcastSyncer,
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->podcastRepository = $podcastRepository;
        $this->podcastSyncer     = $podcastSyncer;
        $this->privilegeChecker  = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Sync and download new podcast episodes
     *
     * filter = (string) UID of podcast
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type?: string,
     *     overwrite?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        // the rest route passes the podcast as `id`
        $filter = $input['filter'] ?? $input['id'] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::CONTENT_MANAGER,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::CONTENT_MANAGER->value)
            );
        }

        $objectId = (int) $filter;
        $podcast  = $this->podcastRepository->findById($objectId);

        if ($podcast === null) {
            throw new ResultEmptyException(
                (string) $objectId
            );
        }

        if (!$this->podcastSyncer->sync($podcast, true)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $objectId),
                    self::ACTION,
                    'podcast'
                )
            );

            return $response;
        }

        Session::extend($input['auth'], AccessTypeEnum::API->value);

        $response->getBody()->write(
            $output->success($apiVersion, 'Synced episodes for podcast: ' . $objectId)
        );

        return $response;
    }
}
