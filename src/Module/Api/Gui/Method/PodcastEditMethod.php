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
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PodcastEditMethod implements MethodInterface
{
    public const ACTION = 'podcast_edit';

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        UiInterface $ui
    ) {
        $this->streamFactory   = $streamFactory;
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
        $this->ui              = $ui;
    }

    /**
     * MINIMUM_API_VERSION=420000
     * CHANGED_IN_API_VERSION=5.0.0
     * Update the description and/or expiration date for an existing podcast.
     * Takes the podcast id to update with optional description and expires parameters.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter      = (string) Alpha-numeric search term
     * feed        = (string) feed url (xml!) //optional
     * title       = (string) title string //optional
     * website     = (string) source website url //optional
     * description = (string) //optional
     * generator   = (string) //optional
     * copyright   = (string) //optional
     *
     * @return ResponseInterface
     *
     * @throws FunctionDisabledException
     * @throws AccessDeniedException
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
            throw new FunctionDisabledException(
                T_('Enable: podcast')
            );
        }

        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER) === false) {
            throw new AccessDeniedException('Require: 50');
        }

        $podcastId = $input['filter'] ?? null;

        if ($podcastId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        $podcastId = (int) $podcastId;
        $podcast   = $this->modelFactory->createPodcast($podcastId);

        if ($podcast->isNew()) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %d'), $podcastId)
            );
        }

        $feed           = filter_var($input['feed'] ?? '', FILTER_VALIDATE_URL) ? $input['feed'] : $podcast->feed;
        $title          = isset($input['title']) ? $this->ui->scrubIn($input['title']) : $podcast->title;
        $website        = filter_var($input['website'] ?? '', FILTER_VALIDATE_URL) ? $this->ui->scrubIn($input['website']) : $podcast->website;
        $description    = isset($input['description']) ? $this->ui->scrubIn($input['description']) : $podcast->description;
        $generator      = isset($input['generator']) ? $this->ui->scrubIn($input['generator']) : $podcast->generator;
        $copyright      = isset($input['copyright']) ? $this->ui->scrubIn($input['copyright']) : $podcast->copyright;
        $data           = array(
            'feed' => $feed,
            'title' => $title,
            'website' => $website,
            'description' => $description,
            'generator' => $generator,
            'copyright' => $copyright
        );
        if (!$podcast->update($data)) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $podcastId)
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(sprintf('podcast %d updated', $podcastId))
            )
        );
    }
}
