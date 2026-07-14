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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns the bookmark for an object_id and object_type.
 *
 * Version 5 matches bookmarks without a client comment and never includes the bookmarked object,
 * so it keeps a method of its own.
 */
final class GetBookmark5Method implements MethodInterface
{
    public const string ACTION = 'get_bookmark';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * get_bookmark
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get the bookmark from it's object_id and object_type.
     *
     * filter = (string) object_id to find
     * type = (string) object_type ('bookmark', 'song', 'video', 'podcast_episode')
     *
     * @param array{
     *     filter: string,
     *     type: string,
     *     include?: int,
     *     all?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
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
        foreach (['filter', 'type'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $object_id = (int) $input['filter'];
        $type      = $input['type'];

        if (
            !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
            && $type == 'video'
        ) {
            throw new AccessDeniedException(
                'Enable: video'
            );
        }

        // confirm the correct data
        if (!in_array(strtolower($type), ['bookmark', 'song', 'video', 'podcast_episode'])) {
            return $this->writeTypeError($response, $output, $apiVersion, $type);
        }

        $className = ObjectTypeToClassNameMapper::map($type);
        if ($className === $type || !$object_id) {
            return $this->writeTypeError($response, $output, $apiVersion, $type);
        }

        /** @var Bookmark|Song|Podcast_Episode|Video $item */
        $item = new $className($object_id);
        if ($item->isNew()) {
            throw new ResultEmptyException(
                (string) $object_id
            );
        }

        $object = [
            'user' => $user->id,
            'object_id' => $object_id,
            'object_type' => $type,
            'comment' => null,
        ];

        $results = Bookmark::getBookmarks($object);
        if ($results === []) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->writeEmpty($apiVersion, 'bookmark')
                )
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->bookmarks($apiVersion, $results, $input['auth'])
            )
        );
    }

    private function writeTypeError(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $type,
    ): ResponseInterface {
        return $response->withBody(
            $this->streamFactory->createStream(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $type),
                    self::ACTION,
                    'type'
                )
            )
        );
    }
}
