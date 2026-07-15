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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Browses the children of a folder
 *
 * `folder` and `folders` only differ in how they find the folder to browse (by its id, or by its
 * path name) and in how that choice narrows the browse. The concrete classes supply both; the
 * browse itself and the output are shared.
 *
 * Only api version 8 knows about folders.
 */
abstract class AbstractFolderMethod implements MethodInterface
{
    // whether the folder listing wraps as a named object; overridden per method
    protected const bool AS_OBJECT = true;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=8.0.0
     *
     * Return the children of a folder
     *
     * filter = (string) the folder, as an id or a path name //optional
     * add    = $browse->set_api_filter(date) //optional
     * update = $browse->set_api_filter(date) //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * cond   = (string) Apply additional filters to the browse //optional
     * sort   = (string) sort name or comma separated key pair //optional
     *
     * @param array{
     *     filter?: int|string,
     *     exact?: int,
     *     add?: string,
     *     update?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
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
        $folder = $this->resolveFolder($input);
        if ($folder === null || $folder->isNew()) {
            throw new ResultEmptyException(
                $this->requestedFolder($input)
            );
        }

        $browse = $this->modelFactory->createBrowse(null, false);
        $browse->set_user_id($user);
        $browse->set_type('folder');

        $this->narrowBrowse($browse, $folder, $input);

        $browse->set_filter('catalog', User::get_user_catalogs($user->getId()));
        $browse->set_api_filter('add', $input['add'] ?? '');
        $browse->set_api_filter('update', $input['update'] ?? '');
        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if (empty($results)) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'folder')
            );

            return $response;
        }

        $output->setOffset($apiVersion, (int) ($input['offset'] ?? 0));
        $output->setLimit($apiVersion, (int) ($input['limit'] ?? 0));

        $response->getBody()->write(
            $output->folders($apiVersion, $results, $folder, $user, $input['auth'], static::AS_OBJECT)
        );

        return $response;
    }

    /**
     * Narrows the browse to the resolved folder
     *
     * @param array<string, mixed> $input
     */
    abstract protected function narrowBrowse(Browse $browse, Folder $folder, array $input): void;

    /**
     * How the request named the folder, for the not-found error
     *
     * @param array<string, mixed> $input
     */
    abstract protected function requestedFolder(array $input): string;

    /**
     * Finds the folder the request asked for, or null when there is none
     *
     * @param array<string, mixed> $input
     */
    abstract protected function resolveFolder(array $input): ?Folder;
}
