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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class GetBookmarkMethod implements MethodInterface
{
    public const ACTION = 'get_bookmark';

    private ModelFactoryInterface $modelFactory;

    private BookmarkRepositoryInterface $bookmarkRepository;

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        BookmarkRepositoryInterface $bookmarkRepository,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory       = $modelFactory;
        $this->bookmarkRepository = $bookmarkRepository;
        $this->streamFactory      = $streamFactory;
        $this->configContainer    = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get the bookmark from it's object_id and object_type.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) object_id to find
     * type   = (string) object_type ('song', 'video', 'podcast_episode')
     *
     * @return ResponseInterface
     *
     * @throws RequestParamMissingException
     * @throws FunctionDisabledException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        foreach (['type', 'filter'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        $type = $input['type'];

        if ($type === 'video' && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_VIDEO) === false) {
            throw new FunctionDisabledException(
                T_('Enable: video')
            );
        }

        if (!in_array($type, ['song', 'video', 'podcast_episode'])) {
            throw new RequestParamMissingException(
                T_('Bad Request')
            );
        }

        $objectId = (int) $input['filter'];

        $item = $this->modelFactory->mapObjectType($type, $objectId);

        if (!$item->id) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %d'), $objectId)
            );
        }

        $bookmarkIds = $this->bookmarkRepository->lookup(
            $type,
            $objectId,
            $gatekeeper->getUser()->getId()
        );

        if ($bookmarkIds === []) {
            $result = $output->emptyResult('bookmark');
        } else {
            $result = $output->bookmarks($bookmarkIds);
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
