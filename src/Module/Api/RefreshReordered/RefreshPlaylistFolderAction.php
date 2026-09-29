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

namespace Ampache\Module\Api\RefreshReordered;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playlist\Folder\PlaylistFolderItemsLoaderInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Re-renders one playlist folder level's children after they have been dragged into a new order
 *
 * Only the table is redrawn, so the page keeps its scroll position; see `RefreshCollectionItemsAction`.
 */
final readonly class RefreshPlaylistFolderAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'refresh_playlist_folder';

    public function __construct(
        private RequestParserInterface $requestParser,
        private BrowseFactoryInterface $browseFactory,
        private PlaylistFolderItemsLoaderInterface $itemsLoader,
        private PlaylistFolderRepositoryInterface $playlistFolderRepository,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $user = $gatekeeper->getUser();
        if ($user === null) {
            return null;
        }

        $folderId = (int) $this->requestParser->getFromRequest('id');
        $folder   = ($folderId > PlaylistFolder::ROOT)
            ? $this->playlistFolderRepository->findById($folderId)
            : null;

        // another user's folder is not this viewer's to refresh, same rule `PlaylistFolderAction` applies
        if ($folderId > PlaylistFolder::ROOT && (!$folder instanceof PlaylistFolder || !$folder->isVisible($user))) {
            return null;
        }

        $browse = $this->browseFactory->create();
        $browse->set_type('playlist_folder');
        $browse->set_show_header(false);
        $browse->set_static_content(true);

        if ($folder instanceof PlaylistFolder) {
            $browse->add_supplemental_object('playlist_folder', $folder);
        }

        $browse->show_objects($this->getRowIds($user, $folder), true);
        $browse->store();

        return null;
    }

    /**
     * The subfolders of this folder, followed by the playlists and smartlists filed in it -- the same set
     * `PlaylistFolderAction::getRowIds()` builds, so a refresh renders exactly what the page's initial load did.
     *
     * @return list<string>
     */
    private function getRowIds(User $user, ?PlaylistFolder $folder): array
    {
        $ids = [];
        foreach ($this->playlistFolderRepository->getChildren($user, $folder?->getId() ?? PlaylistFolder::ROOT) as $child) {
            $ids[] = sprintf('playlist_folder-%d', $child->getId());
        }

        foreach ($this->itemsLoader->getItems($user, $folder) as $item) {
            if ($item['object_type'] === 'collection') {
                continue;
            }

            $ids[] = sprintf('%s-%d', $item['object_type'], $item['object_id']);
        }

        return $ids;
    }
}
