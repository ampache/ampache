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
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PodcastMethod implements MethodInterface
{
    public const ACTION = 'podcast';

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->streamFactory     = $streamFactory;
        $this->configContainer   = $configContainer;
        $this->podcastRepository = $podcastRepository;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Get the podcast from it's id.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter  = (integer) Podcast ID number
     * include = (string) 'episodes' (include episodes in the response) //optional
     *
     * @return ResponseInterface
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

        $include = $input['include'] ?? '';

        $podcast = $this->podcastRepository->findById((int) $objectId);

        if ($podcast === null) {
            throw new ResultEmptyException((string) $objectId);
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->podcasts(
                    [$objectId],
                    $gatekeeper->getUser()->getId(),
                    $include === 'episodes' || (int) $include === 1,
                    false
                )
            )
        );
    }
}
