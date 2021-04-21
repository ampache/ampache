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

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PodcastEpisodesMethod implements MethodInterface
{
    public const ACTION = 'podcast_episodes';

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->streamFactory            = $streamFactory;
        $this->configContainer          = $configContainer;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->podcastRepository        = $podcastRepository;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * This returns the episodes for a podcast
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of podcast
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @return ResponseInterface
     *
     * @throws FunctionDisabledException
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            throw new FunctionDisabledException(T_('Enable: podcast'));
        }

        $objectId = $input['filter'] ?? null;

        if ($objectId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        $podcast = $this->podcastRepository->findById((int) $objectId);

        if ($podcast === null) {
            throw new ResultEmptyException((string) $objectId);
        }

        $items = $this->podcastEpisodeRepository->getEpisodeIds($podcast);

        if ($items === []) {
            $result = $output->emptyResult('podcast_episode');
        } else {
            $result = $output->podcast_episodes(
                $items,
                $gatekeeper->getUser()->getId(),
                false,
                true,
                true,
                (int)($input['limit'] ?? 0),
                (int)($input['offset'] ?? 0)
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
