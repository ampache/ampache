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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the children of a folder, found by its id or by its path name
 *
 * Only api version 8 knows about folders.
 */
final class Folders8Method implements MethodInterface
{
    public const string ACTION = 'folders';

    private FolderRepositoryInterface $folderRepository;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        FolderRepositoryInterface $folderRepository,
        ModelFactoryInterface $modelFactory,
    ) {
        $this->folderRepository = $folderRepository;
        $this->modelFactory     = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=8.0.0
     *
     * Return the children of a folder
     *
     * filter = (string) the folder, as an id or a path name //optional
     * exact  = (integer) 0,1 match a path name loosely when 0 //optional
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
            $output->folders($apiVersion, $results, $folder, $user, $input['auth'])
        );

        return $response;
    }

    /**
     * A filter made up of digits alone is a folder id; a path name always carries its directory separators
     */
    private function isObjectId(string $filter): bool
    {
        return (bool) preg_match('/^-?[0-9]+$/', $filter);
    }

    /**
     * An inexact path name narrows on the path itself; an id, the root and an exact path name narrow on the folder
     *
     * @param array<string, mixed> $input
     */
    private function narrowBrowse(Browse $browse, Folder $folder, array $input): void
    {
        $filter = $this->requestedFolder($input);
        $loose  = !$this->isObjectId($filter) && array_key_exists('exact', $input) && (int) $input['exact'] === 0;

        if (!$loose) {
            $browse->set_filter('int_id', $folder->getId());
        } elseif ($filter !== '/') {
            $browse->set_api_filter('alpha_match', $filter);
        }
    }

    /**
     * How the request named the folder; the root is '/' (or the id -1) when nothing was sent
     *
     * @param array<string, mixed> $input
     */
    private function requestedFolder(array $input): string
    {
        return (string) ($input['filter'] ?? '/');
    }

    /**
     * Finds the folder the request asked for, or null when there is none
     *
     * @param array<string, mixed> $input
     */
    private function resolveFolder(array $input): ?Folder
    {
        $filter = $this->requestedFolder($input);
        if ($this->isObjectId($filter)) {
            return new Folder((int) $filter);
        }

        return ($filter === '/')
            ? new Folder(-1)
            : $this->folderRepository->getByPathName(rtrim($filter, '/'));
    }
}
