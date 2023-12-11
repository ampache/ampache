<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

final class PodcastEpisodesMethod implements MethodInterface
{
    public const ACTION = 'podcast_episodes';

    private PodcastRepositoryInterface $podcastRepository;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        PodcastRepositoryInterface $podcastRepository,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        $this->podcastRepository = $podcastRepository;
        $this->configContainer   = $configContainer;
        $this->modelFactory      = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * This returns the episodes for a podcast
     *
     * filter = (string) ID of the podcast
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *  filter?: string,
     *  offset?: string,
     *  limit?: string
     * } $input
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user
    ): ResponseInterface {
        if (!$this->configContainer->get(ConfigurationKeyEnum::PODCAST)) {
            $response->getBody()->write(
                $output->error(
                    ErrorCodeEnum::ACCESS_DENIED,
                    T_('Enable: podcast'),
                    self::ACTION,
                    'system'
                )
            );

            return $response;
        }

        $podcastId = (int) ($input['filter'] ?? 0);
        $offset    = (int) ($input['offset'] ?? 0);
        $limit     = (int) ($input['limit'] ?? 0);

        if ($podcastId === 0) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        $podcast = $this->modelFactory->createPodcast($podcastId);
        if ($podcast->isNew()) {
            throw new ResultEmptyException(
                (string) $podcastId
            );
        }

        $results = $this->podcastRepository->getEpisodes($podcast);
        if (empty($results)) {
            $response->getBody()->write(
                $output->writeEmpty('podcast_episode')
            );

            return $response;
        }

        $output->setOffset($offset);
        $output->setLimit($limit);

        $response->getBody()->write(
            $output->podcastEpisodes($results, $user)
        );

        return $response;
    }
}
