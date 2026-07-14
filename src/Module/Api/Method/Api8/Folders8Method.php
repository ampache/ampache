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

use Ampache\Module\Api\Method\AbstractFolderMethod;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\ModelFactoryInterface;
use Override;

/**
 * Returns the children of a folder, found by its path name
 *
 * Only api version 8 knows about folders.
 */
final class Folders8Method extends AbstractFolderMethod
{
    public const string ACTION = 'folders';

    private FolderRepositoryInterface $folderRepository;

    public function __construct(
        FolderRepositoryInterface $folderRepository,
        ModelFactoryInterface $modelFactory,
    ) {
        parent::__construct($modelFactory);

        $this->folderRepository = $folderRepository;
    }

    /**
     * An inexact match narrows on the path itself; anything else narrows on the resolved folder
     *
     * @param array<string, mixed> $input
     */
    #[Override]
    protected function narrowBrowse(Browse $browse, Folder $folder, array $input): void
    {
        $pathName = $this->pathName($input);

        $method = (array_key_exists('exact', $input) && (int) $input['exact'] === 0)
            ? 'alpha_match'
            : 'exact_match';

        // the root folder only ever matches on its id
        if ($method === 'exact_match') {
            $browse->set_filter('int_id', $folder->getId());
        } elseif ($pathName !== '/') {
            $browse->set_api_filter($method, $pathName);
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    #[Override]
    protected function requestedFolder(array $input): string
    {
        return $this->pathName($input);
    }

    /**
     * @param array<string, mixed> $input
     */
    #[Override]
    protected function resolveFolder(array $input): ?Folder
    {
        $pathName = $this->pathName($input);

        return ($pathName === '/')
            ? new Folder(-1)
            : $this->folderRepository->getByPathName(rtrim($pathName, '/'));
    }

    /**
     * The root folder is '/'
     *
     * @param array<string, mixed> $input
     */
    private function pathName(array $input): string
    {
        return (string) ($input['filter'] ?? '/');
    }
}
