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
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class BookmarkCreateMethod implements MethodInterface
{
    public const ACTION = 'bookmark_create';

    private BookmarkRepositoryInterface $bookmarkRepository;

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    public function __construct(
        BookmarkRepositoryInterface $bookmarkRepository,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        UiInterface $ui
    ) {
        $this->bookmarkRepository = $bookmarkRepository;
        $this->streamFactory      = $streamFactory;
        $this->configContainer    = $configContainer;
        $this->modelFactory       = $modelFactory;
        $this->ui                 = $ui;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Create a placeholder for the current media that you can return to later.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter   = (string) object_id
     * type     = (string) object_type ('song', 'video', 'podcast_episode')
     * position = (integer) current track time in seconds
     * client   = (string) Agent string Default: 'AmpacheAPI' // optional
     * date     = (integer) UNIXTIME() //optional
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
        $objectId  = $input['filter'] ?? null;
        $position  = $input['position'] ?? null;

        if ($objectId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }
        if ($position === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'position')
            );
        }

        $type = $input['type'] ?? '';

        // confirm the correct data
        if (!in_array($type, ['song', 'video', 'podcast_episode'])) {
            throw new RequestParamMissingException(
                T_('Bad Request')
            );
        }

        if ($type === 'video' && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_VIDEO) === false) {
            throw new FunctionDisabledException(
                T_('Enable: video')
            );
        }

        $comment = $input['client'] ?? 'AmpacheAPI';
        $time    = (int) ($input['date'] ?? time());

        /** @var Song|Video|Podcast_Episode $mediaObject */
        $mediaObject = $this->modelFactory->mapObjectType($type, (int) $objectId);

        if ($mediaObject === null || $mediaObject->isNew()) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $objectId)
            );
        }

        $bookmarkId = $this->bookmarkRepository->create(
            (int) $position,
            $this->ui->scrubIn($comment),
            $type,
            $mediaObject->getId(),
            $gatekeeper->getUser()->getId(),
            $time
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->bookmarks([$bookmarkId])
            )
        );
    }
}
