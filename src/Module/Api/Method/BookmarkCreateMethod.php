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
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Creates a placeholder for the current media that can be returned to later
 */
final class BookmarkCreateMethod implements MethodInterface
{
    public const string ACTION = 'bookmark_create';

    public const string REST_ACTION = 'bookmarks_create';

    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Create a placeholder for the current media that you can return to later.
     *
     * filter   = (string) object_id
     * type     = (string) object_type ('song', 'video', 'podcast_episode')
     * position = (integer) current track time in seconds
     * client   = (string) Agent string //optional
     * date     = (integer) UNIXTIME() //optional
     * include  = (integer) 0,1, if true include the object in the bookmark //optional
     *
     * @param array{
     *     filter?: string,
     *     type?: string,
     *     position?: string,
     *     client?: string,
     *     date?: int,
     *     include?: int,
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
        foreach (['filter', 'type', 'position'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $objectId = (int) $input['filter'];
        $type     = (string) $input['type'];
        $position = (int) $input['position'];
        $comment  = (isset($input['client'])) ? scrub_in((string) $input['client']) : null;
        $time     = (isset($input['date'])) ? (int) $input['date'] : time();
        $include  = make_bool($input['include'] ?? false);

        if (
            $type === 'video'
            && !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
        ) {
            throw new AccessDeniedException(
                'Enable: video'
            );
        }

        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'video', 'podcast_episode'])) {
            return $this->writeTypeError($response, $output, $apiVersion, $type);
        }

        $className = ObjectTypeToClassNameMapper::map($type);
        if ($className === $type || !$objectId) {
            return $this->writeTypeError($response, $output, $apiVersion, $type);
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

        $object = [
            'user' => $user->getId(),
            'object_id' => $objectId,
            'object_type' => $type,
            'comment' => $comment,
            'position' => $position,
        ];

        // create it then retrieve it
        Bookmark::create($object, $user->getId(), $time);

        $results = Bookmark::getBookmarks($object);
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, null)
            );

            return $response;
        }

        // only return the most recent bookmark
        $response->getBody()->write(
            $output->bookmarks($apiVersion, array_slice($results, 0, 1), $input['auth'], $include, false)
        );

        return $response;
    }

    private function writeTypeError(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $type,
    ): ResponseInterface {
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
}
