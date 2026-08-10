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
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Rates a library item.
 *
 * Version 5 reads the object id from `id` only and knows nothing about the smart playlists the
 * later versions accept, so it keeps a method of its own.
 */
final class Rate5Method implements MethodInterface
{
    public const string ACTION = 'rate';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * rate
     * MINIMUM_API_VERSION=380001
     *
     * This rates a library item
     *
     * type = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video' $type
     * id = (integer) $object_id
     * rating = (integer) 0|1|2|3|4|5 $rating
     *
     * @param array{
     *     type: string,
     *     id: string,
     *     rating: int,
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
        if (!$this->configContainer->get(ConfigurationKeyEnum::RATINGS)) {
            throw new AccessDeniedException(
                'Enable: ratings'
            );
        }

        foreach (['type', 'id', 'rating'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        $rating    = (string) $input['rating'];

        // confirm the correct data
        if (!Rating::is_valid(strtolower($type))) {
            return $this->writeTypeError($response, $output, $apiVersion, $type);
        }

        if (!in_array($rating, ['0', '1', '2', '3', '4', '5'])) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: %s', $rating),
                        self::ACTION,
                        'rating'
                    )
                )
            );
        }

        $className = ObjectTypeToClassNameMapper::map($type);
        if (!$className || !$object_id) {
            return $this->writeTypeError($response, $output, $apiVersion, $type);
        }

        /** @var library_item $item */
        $item = new $className($object_id);
        if ($item->isNew()) {
            throw new ResultEmptyException(
                (string) $object_id,
                'id'
            );
        }

        $rate = new Rating($object_id, $type);
        $rate->set_rating((int) $rating, $user->id);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success($apiVersion, 'rating set to ' . $rating . ' for ' . $object_id)
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
