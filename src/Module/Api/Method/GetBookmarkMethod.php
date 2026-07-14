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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the bookmark of a given object for the current user.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class GetBookmarkMethod implements MethodInterface
{
    public const string ACTION = 'get_bookmark';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get the bookmark from it's object_id and object_type
     *
     * filter  = (string) object_id
     * type    = (string) object_type ('bookmark', 'song', 'video', 'podcast_episode')
     * include = (integer) 0,1, if true include the object in the bookmark //optional
     * all     = (integer) 0,1, if true include every bookmark for the object //optional
     *
     * @param array{
     *     filter?: string,
     *     type?: string,
     *     include?: int|bool,
     *     all?: int|bool,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 6|8 $apiVersion
     * @throws RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        foreach (['filter', 'type'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $objectId = (int) $input['filter'];
        $type     = (string) $input['type'];
        $include  = make_bool($input['include'] ?? false);
        $all      = make_bool($input['all'] ?? false);

        if (
            $type === 'video'
            && !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
        ) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::ACCESS_DENIED,
                    'Enable: video',
                    self::ACTION,
                    'system'
                )
            );

            return $response;
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
            'bookmark' => new Bookmark($objectId),
            'song' => $this->modelFactory->createSong($objectId),
            'video' => $this->modelFactory->createVideo($objectId),
            'podcast_episode' => $this->modelFactory->createPodcastEpisode($objectId),
            default => null,
        };

        if (
            $item === null
            || $item->isNew()
        ) {
            throw new ResultEmptyException((string) $objectId);
        }

        $results = Bookmark::getBookmarks([
            'user' => $user->id,
            'object_id' => $objectId,
            'object_type' => $type,
            'comment' => null,
        ]);

        if (
            $results === []
            && !$all
        ) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, null)
            );

            return $response;
        }

        if (!$all) {
            $results = array_slice($results, 0, 1);
        }

        $response->getBody()->write(
            $output->bookmarks($apiVersion, $results, $input['auth'], $include, $all)
        );

        return $response;
    }
}
