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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Adds an object to a collection
 */
final class CollectionAdd8Method implements MethodInterface
{
    use CollectionLoaderTrait;

    public const string ACTION = 'collection_add';

    public const string REST_ACTION = 'collection_items_create';

    private CollectionRepositoryInterface $collectionRepository;

    public function __construct(
        CollectionRepositoryInterface $collectionRepository,
    ) {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * collection_add
     * MINIMUM_API_VERSION=800000
     *
     * Add one object to a collection. Adding the same object twice is a no-op, not a duplicate.
     *
     * filter      = (string) UID of Collection
     * id          = (string) UID of the object to add
     * object_type = (string) type of the object to add
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     object_type?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     *
     * @throws AccessDeniedException
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $collection = $this->loadEditableCollection($input, $user);

        foreach (['id', 'object_type'] as $param) {
            if (!array_key_exists($param, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $param)
                );
            }
        }

        $objectType = (string) $input['object_type'];
        $objectId   = (int) $input['id'];

        // A pinned collection rejects anything but its own type; a mixed one takes anything in VALID_TYPES.
        if (!$collection->acceptsType($objectType)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: collection does not accept %s items', $objectType),
                    self::ACTION,
                    'object_type'
                )
            );

            return $response;
        }

        // The object has to exist before it is curated, or the collection collects dangling ids.
        if (!$this->collectionRepository->objectExists($objectType, $objectId)) {
            throw new ResultEmptyException((string) $objectId);
        }

        $this->collectionRepository->addItem($collection->getId(), $objectId, $objectType);

        $response->getBody()->write(
            $output->success($apiVersion, 'added to collection')
        );

        return $response;
    }
}
