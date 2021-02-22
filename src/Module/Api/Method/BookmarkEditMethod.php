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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class BookmarkEditMethod implements MethodInterface
{
    public const ACTION = 'bookmark_edit';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    private BookmarkRepositoryInterface $bookmarkRepository;

    private UiInterface $ui;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer,
        BookmarkRepositoryInterface $bookmarkRepository,
        UiInterface $ui
    ) {
        $this->streamFactory      = $streamFactory;
        $this->modelFactory       = $modelFactory;
        $this->configContainer    = $configContainer;
        $this->bookmarkRepository = $bookmarkRepository;
        $this->ui                 = $ui;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Edit a placeholder for the current media that you can return to later.
     *
     * @param array $input
     * filter   = (string) object_id
     * type     = (string) object_type ('song', 'video', 'podcast_episode')
     * position = (integer) current track time in seconds
     * client   = (string) Agent string Default: 'AmpacheAPI' // optional
     * date     = (integer) UNIXTIME() //optional
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
        foreach (['filter', 'position', 'type'] as $key) {
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

        // confirm the correct data
        if (!in_array($type, ['song', 'video', 'podcast_episode'])) {
            throw new RequestParamMissingException(
                T_('Bad Request')
            );
        }

        $objectId  = (int) $input['filter'];
        $position  = (int) $input['position'];
        $comment   = $this->ui->scrubIn($input['client'] ?? 'AmpacheAPI');
        $time      = (int) ($input['date'] ?? time());

        $item = $this->modelFactory->mapObjectType($type, $objectId);

        if (!$item->id) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %d'), $objectId)
            );
        }
        $userId = $gatekeeper->getUser()->getId();

        $bookmarkIds = $this->bookmarkRepository->lookup(
            $type,
            $objectId,
            $userId,
            $comment
        );

        if ($bookmarkIds === []) {
            $result = $output->emptyResult('bookmark');
        } else {
            $this->bookmarkRepository->edit(
                $position,
                $comment,
                $type,
                $objectId,
                $userId,
                $time
            );

            $result = $output->bookmarks($bookmarkIds);
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
