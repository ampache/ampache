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
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PodcastDeleteMethod implements MethodInterface
{
    public const ACTION = 'podcast_delete';

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private PodcastDeleterInterface $podcastDeleter;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        UpdateInfoRepositoryInterface $updateInfoRepository,
        PodcastDeleterInterface $podcastDeleter,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->streamFactory        = $streamFactory;
        $this->configContainer      = $configContainer;
        $this->updateInfoRepository = $updateInfoRepository;
        $this->podcastDeleter       = $podcastDeleter;
        $this->podcastRepository    = $podcastRepository;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing podcast.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of podcast to delete
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     * @throws FunctionDisabledException|AccessDeniedException
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

        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException('Require: 75');
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

        if (!$this->podcastDeleter->delete($podcast)) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $objectId)
            );
        }
        $this->updateInfoRepository->updateCountByTableName('podcast');

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    sprintf('podcast %s deleted', $objectId)
                )
            )
        );
    }
}
