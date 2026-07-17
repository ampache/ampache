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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Deletes a podcast
 */
final class PodcastDeleteMethod implements MethodInterface
{
    public const string ACTION = 'podcast_delete';

    public const string REST_ACTION = 'podcasts_delete';

    private ConfigContainerInterface $configContainer;
    private PodcastDeleterInterface $podcastDeleter;
    private PodcastRepositoryInterface $podcastRepository;
    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PodcastDeleterInterface $podcastDeleter,
        ConfigContainerInterface $configContainer,
        PrivilegeCheckerInterface $privilegeChecker,
        PodcastRepositoryInterface $podcastRepository,
    ) {
        $this->podcastDeleter    = $podcastDeleter;
        $this->configContainer   = $configContainer;
        $this->privilegeChecker  = $privilegeChecker;
        $this->podcastRepository = $podcastRepository;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing podcast.
     *
     * filter = (string) ID of podcast to delete
     *
     * @param array{
     *     filter?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|AccessFailedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!$this->configContainer->get(ConfigurationKeyEnum::PODCAST)) {
            throw new AccessDeniedException(
                'Enable: podcast'
            );
        }

        if (!$this->privilegeChecker->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $user->getId())) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::MANAGER->value)
            );
        }

        $podcastId = (int) ($input['filter'] ?? 0);
        if ($podcastId === 0) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $podcast = $this->podcastRepository->findById($podcastId);

        if ($podcast === null) {
            throw new ResultEmptyException(
                (string) $podcastId
            );
        }

        $this->podcastDeleter->delete($podcast);

        $response->getBody()->write(
            $output->success($apiVersion, sprintf('podcast %d deleted', $podcastId))
        );

        return $response;
    }
}
