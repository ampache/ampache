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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Removes an object from a collection
 */
final class CollectionRemove8Method implements MethodInterface
{
    use CollectionLoaderTrait;

    public const string ACTION = 'collection_remove';

    public const string REST_ACTION = 'collection_items_delete';

    private CollectionRepositoryInterface $collectionRepository;

    public function __construct(
        CollectionRepositoryInterface $collectionRepository,
    ) {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * collection_remove
     * MINIMUM_API_VERSION=800000
     *
     * Remove members from a collection. The objects themselves are untouched.
     *
     * Name either a `track` position or an `id`/`object_type` pair. A collection may hold the same object twice,
     * so naming the object removes every copy of it while naming the position removes exactly one member.
     * The remaining positions close up so the order stays dense.
     *
     * filter      = (string) UID of Collection
     * track       = (integer) position of the member to remove //optional
     * id          = (string) UID of the object to remove //optional
     * object_type = (string) type of the object to remove //optional
     *
     * @param array{
     *     filter?: string,
     *     track?: int|string,
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

        // Removing something that is not a member is not an error: the caller asked for it gone, and it is.
        if (array_key_exists('track', $input)) {
            $collection->delete_track_number((int) $input['track']);

            $response->getBody()->write(
                $output->success($apiVersion, 'removed from collection')
            );

            return $response;
        }

        foreach (['id', 'object_type'] as $param) {
            if (!array_key_exists($param, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $param)
                );
            }
        }

        $this->collectionRepository->removeItem(
            $collection->getId(),
            (int) $input['id'],
            (string) $input['object_type']
        );

        // Naming an object can drop several members at once, so the positions are closed up afterwards
        $collection->regenerate_track_numbers();

        $response->getBody()->write(
            $output->success($apiVersion, 'removed from collection')
        );

        return $response;
    }
}
