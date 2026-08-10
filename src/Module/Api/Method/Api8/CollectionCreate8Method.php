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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Creates a collection
 */
final class CollectionCreate8Method implements MethodInterface
{
    public const string ACTION = 'collection_create';

    public const string REST_ACTION = 'collections_create';

    private CollectionRepositoryInterface $collectionRepository;

    public function __construct(
        CollectionRepositoryInterface $collectionRepository,
    ) {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * collection_create
     * MINIMUM_API_VERSION=800000
     *
     * Create a collection, optionally pinned to a single object type
     *
     * name        = (string) collection name
     * type        = (string) 'public'|'private' //optional, default 'private'
     * object_type = (string) pin the collection to one type //optional, mixed when omitted
     *
     * @param array{
     *     name?: string,
     *     type?: string,
     *     object_type?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     *
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!array_key_exists('name', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'name')
            );
        }

        $type = ($input['type'] ?? 'private') === 'public' ? 'public' : 'private';

        // An unknown pinned type is rejected rather than stored: it would accept no member at all
        $objectType = (isset($input['object_type'])) ? (string) $input['object_type'] : null;
        if ($objectType !== null && !Collection::isValidType($objectType)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'object_type')
            );
        }

        $collectionId = $this->collectionRepository->create((string) $input['name'], $user, $type, $objectType);
        if ($collectionId === null) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Bad Request',
                    self::ACTION,
                    'input'
                )
            );

            return $response;
        }

        $response->getBody()->write(
            $output->collections($apiVersion, [$collectionId], $user, $input['auth'])
        );

        return $response;
    }
}
