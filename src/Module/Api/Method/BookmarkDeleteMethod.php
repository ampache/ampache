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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Deletes an existing bookmark
 */
final class BookmarkDeleteMethod implements MethodInterface
{
    public const string ACTION = 'bookmark_delete';

    public const string REST_ACTION = 'bookmarks_delete';

    private BookmarkRepositoryInterface $bookmarkRepository;
    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        BookmarkRepositoryInterface $bookmarkRepository,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
    ) {
        $this->bookmarkRepository = $bookmarkRepository;
        $this->configContainer    = $configContainer;
        $this->modelFactory       = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Delete an existing bookmark. (if it exists)
     *
     * filter = (string) object_id to delete
     * type   = (string) object_type ('bookmark', 'song', 'video', 'podcast_episode') //optional default: bookmark
     * client = (string) Agent string //optional
     *
     * @param array{
     *     filter?: string,
     *     type?: string,
     *     client?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $objectId = (int) $input['filter'];
        $type     = $input['type'] ?? 'bookmark';
        $comment  = (isset($input['client'])) ? scrub_in((string) $input['client']) : null;

        if (
            $type === 'video'
            && !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
        ) {
            throw new AccessDeniedException(
                'Enable: video'
            );
        }

        // confirm the correct data
        if (!in_array(strtolower($type), ['bookmark', 'song', 'video', 'podcast_episode'])) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $type),
                    self::ACTION,
                    'type'
                )
            );

            return $response;
        }

        if ($type !== 'bookmark') {
            $className = ObjectTypeToClassNameMapper::map($type);
            if ($className === $type || !$objectId) {
                $response->getBody()->write(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: %s', $type),
                        self::ACTION,
                        'type'
                    )
                );

                return $response;
            }

            // the mapper check above guarantees a canonically named type, so the default is unreachable
            $item = match ($type) {
                'song' => $this->modelFactory->createSong($objectId),
                'video' => $this->modelFactory->createVideo($objectId),
                'podcast_episode' => $this->modelFactory->createPodcastEpisode($objectId),
                default => null,
            };

            if (
                $item === null
                || $item->isNew()
            ) {
                throw new ResultEmptyException(
                    (string) $objectId
                );
            }
        }

        $find = Bookmark::getBookmarks([
            'object_type' => $type,
            'object_id' => $objectId,
            'comment' => $comment,
            'user' => $user->getId(),
        ]);

        if ($find === []) {
            throw new ResultEmptyException(
                (string) $objectId,
                'bookmark'
            );
        }

        $this->bookmarkRepository->delete((int) current($find));

        $response->getBody()->write(
            $output->success($apiVersion, 'Deleted Bookmark: ' . $objectId)
        );

        return $response;
    }
}
