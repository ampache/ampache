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

use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\User;

/**
 * Loading and authorising the collection named by `filter`, shared by every collection method.
 */
trait CollectionLoaderTrait
{
    /**
     * The collection named by `filter`. One the user may not see reports not found rather than denied.
     *
     * @param array<string, mixed> $input
     *
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    private function loadCollection(array $input, User $user): Collection
    {
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $objectId   = (int) $input['filter'];
        $collection = $this->collectionRepository->findById($objectId);
        if (
            $collection === null
            || !$collection->isVisible($user)
        ) {
            throw new ResultEmptyException((string) $objectId);
        }

        return $collection;
    }

    /**
     * The collection named by `filter`, refused unless the caller may edit it (owner, admin or collaborator).
     *
     * @param array<string, mixed> $input
     *
     * @throws AccessDeniedException
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    private function loadEditableCollection(array $input, User $user): Collection
    {
        $collection = $this->loadCollection($input, $user);
        if (!$collection->has_collaborate($user)) {
            throw new AccessDeniedException(
                sprintf('Require: %s', 'collection owner or collaborator')
            );
        }

        return $collection;
    }
}
