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

namespace Ampache\Module\Application\Browse;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playlist\Folder\PlaylistFolderItemsLoaderInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * One level of a user's playlist folder tree: its subfolders plus the playlists and smartlists filed there.
 *
 * Collections are a valid folder member too, but are left out of this browse for now.
 */
final readonly class PlaylistFolderAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'playlist_folder';

    public function __construct(
        private BrowseFactoryInterface $browseFactory,
        private PlaylistFolderItemsLoaderInterface $itemsLoader,
        private PlaylistFolderRepositoryInterface $playlistFolderRepository,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
            throw new AccessDeniedException('Access Denied: playlist folders are only available to a logged in user.');
        }

        $user = $gatekeeper->getUser();
        if ($user === null) {
            throw new AccessDeniedException('Access Denied: playlist folders are only available to a logged in user.');
        }

        $input    = $request->getQueryParams();
        $folderId = (isset($input['folder'])) ? (int) $input['folder'] : PlaylistFolder::ROOT;
        $folder   = ($folderId > PlaylistFolder::ROOT)
            ? $this->playlistFolderRepository->findById($folderId)
            : null;

        // another user's folder is not yours to browse, and a stale/removed id is not distinguishable from it
        if ($folderId > PlaylistFolder::ROOT && (!$folder instanceof PlaylistFolder || !$folder->isVisible($user))) {
            throw new AccessDeniedException('Access Denied: playlist folder filter');
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $browse = $this->browseFactory->create();
        $browse->set_type(self::REQUEST_KEY);
        $browse->set_use_pages(true);

        $this->ui->showHeader();

        if ($folder instanceof PlaylistFolder) {
            $browse->add_supplemental_object(self::REQUEST_KEY, $folder);
        }

        $browse->show_objects($this->getRowIds($user, $folder));

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }

    /**
     * The subfolders of this folder, followed by the playlists and smartlists filed in it, each id encoded as
     * `playlist_folder-N`/`playlist-N`/`search-N` for `PlaylistFolderListRenderer` to split back apart.
     *
     * @return list<string>
     */
    private function getRowIds(User $user, ?PlaylistFolder $folder): array
    {
        $ids = [];
        foreach ($this->playlistFolderRepository->getChildren($user, $folder?->getId() ?? PlaylistFolder::ROOT) as $child) {
            $ids[] = sprintf('%s-%d', self::REQUEST_KEY, $child->getId());
        }

        foreach ($this->itemsLoader->getItems($user, $folder) as $item) {
            // collections are a valid folder member, but this browse does not surface them yet
            if ($item['object_type'] === 'collection') {
                continue;
            }

            $ids[] = sprintf('%s-%d', $item['object_type'], $item['object_id']);
        }

        return $ids;
    }
}
