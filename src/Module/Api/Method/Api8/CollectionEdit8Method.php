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
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Renames, retypes or re-pins a collection
 */
final class CollectionEdit8Method implements MethodInterface
{
    use CollectionLoaderTrait;

    public const string ACTION = 'collection_edit';

    private CollectionRepositoryInterface $collectionRepository;

    public function __construct(
        CollectionRepositoryInterface $collectionRepository,
    ) {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * collection_edit
     * MINIMUM_API_VERSION=800000
     *
     * Change a collection's name, visibility, pinned type or collaborators. Only the fields supplied change.
     *
     * filter      = (string) UID of Collection
     * name        = (string) //optional
     * type        = (string) 'public'|'private' //optional
     * object_type = (string) pinned type, or empty string to un-pin back to mixed //optional
     * collaborate = (string) comma separated user ids //optional
     *
     * @param array{
     *     filter?: string,
     *     name?: string,
     *     type?: string,
     *     object_type?: string,
     *     collaborate?: string,
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

        $objectType = (isset($input['object_type'])) ? (string) $input['object_type'] : null;
        if ($objectType !== null && $objectType !== '' && !Collection::isValidType($objectType)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'object_type')
            );
        }

        // `conflictingType()` is the shared authority so this and the web edit dialog cannot drift apart
        if ($objectType !== null && $objectType !== '') {
            $conflict = $collection->conflictingType($objectType);
            if ($conflict !== null) {
                $response->getBody()->write(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: collection already holds %s items', $conflict),
                        self::ACTION,
                        'object_type'
                    )
                );

                return $response;
            }
        }

        $type = (isset($input['type']))
            ? (($input['type'] === 'public') ? 'public' : 'private')
            : null;

        $this->collectionRepository->update(
            $collection->getId(),
            (isset($input['name'])) ? (string) $input['name'] : null,
            $type,
            $objectType,
            (isset($input['collaborate'])) ? (string) $input['collaborate'] : null
        );

        $response->getBody()->write(
            $output->collections($apiVersion, [$collection->getId()], $user, $input['auth'])
        );

        return $response;
    }
}
