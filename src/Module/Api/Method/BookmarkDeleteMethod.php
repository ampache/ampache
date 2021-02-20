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

final class BookmarkDeleteMethod implements MethodInterface
{
    public const ACTION = 'bookmark_delete';

    private BookmarkRepositoryInterface $bookmarkRepository;

    private ModelFactoryInterface $modelFactory;

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    public function __construct(
        BookmarkRepositoryInterface $bookmarkRepository,
        ModelFactoryInterface $modelFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        UiInterface $ui
    ) {
        $this->bookmarkRepository = $bookmarkRepository;
        $this->modelFactory       = $modelFactory;
        $this->streamFactory      = $streamFactory;
        $this->configContainer    = $configContainer;
        $this->ui                 = $ui;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Delete an existing bookmark. (if it exists)
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) object_id to delete
     * type   = (string) object_type  ('song', 'video', 'podcast_episode')
     * client = (string) Agent string Default: 'AmpacheAPI' // optional
     *
     * @return ResponseInterface
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
        foreach (['filter', 'type',] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        $objectId = (int) $input['filter'];
        $type     = $input['type'];

        if (
            $type == 'video' &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_VIDEO) === false
        ) {
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

        $comment = $input['client'] ?? 'AmpacheAPI';

        /** @var Song|Video|Podcast_Episode $item */
        $item = $this->modelFactory->mapObjectType($type, $objectId);

        if (!$item->id) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $objectId)
            );
        }

        $find = $this->bookmarkRepository->lookup(
            $type,
            $objectId,
            $gatekeeper->getUser()->getId(),
            $this->ui->scrubIn($comment)
        );
        if ($find === []) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->emptyResult('bookmark')
                )
            );
        }

        $bookmark = $this->bookmarkRepository->delete(current($find));
        if (!$bookmark) {
            throw new RequestParamMissingException(
                T_('Bad Request')
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    sprintf('Deleted Bookmark: %d', $objectId)
                )
            )
        );
    }
}
