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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\playlist_object;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Override;

/**
 * One level of a user's playlist folder tree: its subfolders plus the playlists and smartlists filed there.
 *
 * `Playlist` and `Search` share their display columns through the `playlist_object` base they both extend, so
 * one row shape covers both; a subfolder is the one row kind that is not a library item at all.
 */
final class PlaylistFolderListRenderer extends AbstractBrowseListRenderer
{
    private const string TYPE_FOLDER = 'playlist_folder';

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly GuiFactoryInterface $guiFactory,
        private readonly PlaylistFolderRepositoryInterface $playlistFolderRepository,
        private readonly ZipHandlerInterface $zipHandler,
    ) {}

    /**
     * The path from the root down to (but not including) the current folder, for the breadcrumb.
     *
     * @return list<PlaylistFolder>
     */
    public function getAncestors(): array
    {
        return $this->cachePerRender('ancestors', function (): array {
            $crumbs   = [];
            $parentId = $this->getCurrentFolder()?->getParentId() ?? PlaylistFolder::ROOT;
            while ($parentId > PlaylistFolder::ROOT) {
                $parent = $this->playlistFolderRepository->findById($parentId);
                if ($parent === null) {
                    break;
                }

                $crumbs[] = $parent;
                $parentId = $parent->getParentId();
            }

            return array_reverse($crumbs);
        });
    }

    /**
     * @return list<array{class: string, label: string, footer: bool}>
     */
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_type essential', 'label' => '', 'footer' => false],
            ['class' => 'cel_name essential persist', 'label' => T_('Name'), 'footer' => false],
            ['class' => 'cel_last_update optional', 'label' => T_('Last Update'), 'footer' => false],
            ['class' => 'cel_count optional', 'label' => T_('# Items'), 'footer' => false],
            ['class' => 'cel_owner essential', 'label' => T_('Owner'), 'footer' => false],
            ['class' => 'cel_action essential', 'label' => T_('Actions'), 'footer' => false],
        ];
    }

    public function getCreateFolderUrl(): string
    {
        $folderId = $this->getCurrentFolderId();
        $suffix   = ($folderId > PlaylistFolder::ROOT) ? '&folder=' . $folderId : '';

        return $this->configContainer->getWebPath() . '/playlist_folder.php?action=show_create' . $suffix;
    }

    public function getCreatePlaylistUrl(): string
    {
        return $this->configContainer->getWebPath() . '/playlist.php?action=show_create';
    }

    public function getCreateSmartPlaylistUrl(): string
    {
        return $this->configContainer->getWebPath() . '/search.php?type=song';
    }

    /**
     * The folder this browse is showing the contents of; null at the root, which has no row of its own.
     */
    public function getCurrentFolder(): ?PlaylistFolder
    {
        $folder = $this->getSupplementalObject(self::TYPE_FOLDER);

        return ($folder instanceof PlaylistFolder) ? $folder : null;
    }

    /**
     * The folder this browse is showing the contents of, or `PlaylistFolder::ROOT` at the top level.
     */
    public function getCurrentFolderId(): int
    {
        return $this->getCurrentFolder()?->getId() ?? PlaylistFolder::ROOT;
    }

    public function getDeleteFolderUrl(int $folderId): string
    {
        return $this->configContainer->getWebPath() . '/playlist_folder.php?action=delete&folder=' . $folderId;
    }

    public function getEditFolderUrl(int $folderId): string
    {
        return $this->configContainer->getWebPath() . '/playlist_folder.php?action=show_edit&folder=' . $folderId;
    }

    public function getFolderUrl(int $folderId): string
    {
        $suffix = ($folderId > PlaylistFolder::ROOT) ? '&folder=' . $folderId : '';

        return $this->configContainer->getWebPath() . '/browse.php?action=playlist_folder' . $suffix;
    }

    public function getRowItemCount(PlaylistFolder|playlist_object $item): int
    {
        if ($item instanceof PlaylistFolder) {
            return $this->getItemCounts()[$item->getId()] ?? 0;
        }

        return (int) $item->last_count;
    }

    public function getRowLastUpdate(PlaylistFolder|playlist_object $item): string
    {
        $lastUpdate = ($item instanceof PlaylistFolder) ? $item->last_update : (int) $item->last_update;

        return ($lastUpdate > 0) ? get_datetime($lastUpdate) : T_('Unknown');
    }

    public function getRowName(PlaylistFolder|playlist_object $item): string
    {
        if ($item instanceof PlaylistFolder) {
            return '<a href="' . $this->e($this->getFolderUrl($item->getId())) . '">' . $this->e($item->getName()) . '</a>';
        }

        return $item->get_f_link();
    }

    public function getRowOwner(PlaylistFolder|playlist_object $item): string
    {
        return ($item instanceof PlaylistFolder) ? '' : (string) $item->username;
    }

    /**
     * @return list<array{type: string, item: PlaylistFolder|playlist_object}>
     */
    public function getRows(): array
    {
        /** @var list<array{type: string, item: PlaylistFolder|playlist_object}> */
        return $this->cachePerRender('rows', function (): array {
            $rows = [];
            foreach ($this->getContext()->objectIds as $object) {
                [$type, $objectId] = $this->parse((string) $object);
                if ($objectId <= 0) {
                    continue;
                }

                $item = match ($type) {
                    self::TYPE_FOLDER => $this->playlistFolderRepository->findById($objectId),
                    'search' => new Search($objectId, 'song'),
                    'playlist' => new Playlist($objectId),
                    default => null,
                };

                if ($item === null || $item->isNew()) {
                    continue;
                }

                $rows[] = ['type' => $type, 'item' => $item];
            }

            return $rows;
        });
    }

    public function getRowType(string $type): string
    {
        return match ($type) {
            self::TYPE_FOLDER => T_('Folder'),
            'search' => T_('Smart Playlist'),
            default => T_('Playlist'),
        };
    }

    /**
     * The header box title: plain text at the root (nothing to link back to), a breadcrumb of ancestor
     * links ending in the plain current folder name otherwise -- same shape as `FolderView::getTitle()`.
     */
    public function getTitle(): string
    {
        $home = $this->e(T_('Home'));

        $folder = $this->getCurrentFolder();
        if ($folder === null) {
            return $home;
        }

        $crumbs = ['<a href="' . $this->e($this->getFolderUrl(PlaylistFolder::ROOT)) . '">' . $home . '</a>'];
        foreach ($this->getAncestors() as $ancestor) {
            $crumbs[] = '<a href="' . $this->e($this->getFolderUrl($ancestor->getId())) . '">' . $this->e($ancestor->getName()) . '</a>';
        }

        $crumbs[] = $this->e($folder->getName());

        return implode(' / ', $crumbs);
    }

    public function mayCreate(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    /**
     * The same Actions cell the standalone playlist/smart-playlist browses show for this item, so folder
     * assignment (now in the edit dialog, not here) is the only thing this browse does differently.
     */
    public function renderPlaylistActions(Playlist $item): string
    {
        $gatekeeper = $this->gatekeeperFactory->createGuiGatekeeper();
        $playlist   = $this->guiFactory->createPlaylistViewAdapter($gatekeeper, $item);
        $playlistId = $playlist->getId();
        $html       = '';

        if ($playlist->canShare()) {
            $html .= $playlist->getShareUi();
        }

        if ($playlist->canBatchDownload()) {
            $html .= '<a class="nohtml" rel="nofollow" href="' . $this->e($playlist->getBatchDownloadUrl()) . '">' . $playlist->getBatchDownloadIcon() . '</a>';
        }

        if ($playlist->canBeRefreshed()) {
            $html .= '<a href="' . $this->e($playlist->getRefreshUrl()) . '">' . $playlist->getRefreshIcon() . '</a>';
        }

        if ($playlist->isEditable()) {
            // Empty refresh prefix reloads the whole browse instead of one row -- a folder change moves the row out of view entirely
            $html .= '<a id="edit_playlist_' . $playlistId . '" onclick="showEditDialog(\'playlist_row\', \'' . $playlistId . '\', \'edit_playlist_' . $playlistId . '\', \'' . $this->e($playlist->getEditButtonTitle()) . '\', \'\')">' . $playlist->getEditIcon() . '</a>';
        }

        if ($playlist->canBeDeleted()) {
            $html .= $playlist->getDeletionButton();
        }

        return $html;
    }

    /**
     * The same Actions cell the standalone smart-playlist browse shows for this item.
     */
    public function renderSearchActions(Search $item): string
    {
        $searchId = $item->id;
        $html     = '';

        if (Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('search')) {
            $html .= '<a class="nohtml" href="' . $this->e($this->configContainer->getWebPath() . '/batch.php?action=search&id=' . $searchId) . '" rel="nofollow">' . Ui::get_material_symbol('folder_zip', T_('Batch download')) . '</a>';
        }

        if ($item->has_access()) {
            $title = addslashes(T_('Smart Playlist Edit'));
            // Empty refresh prefix reloads the whole browse instead of one row -- a folder change moves the row out of view entirely
            $html .= '<a id="edit_playlist_' . $searchId . '" onclick="showEditDialog(\'search_row\', \'' . $searchId . '\', \'edit_playlist_' . $searchId . '\', \'' . $title . '\', \'\')">' . Ui::get_material_symbol('edit', T_('Edit')) . '</a>';
            $html .= Ajax::button('?page=browse&action=delete_object&type=smartplaylist&id=' . $searchId, 'close', T_('Delete'), 'delete_playlist_' . $searchId, '', '', T_('Are You Sure?'));
        }

        return $html;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/playlist_folders.phtml');
    }

    /**
     * How many lists sit in each of this user's folders, fetched once per render rather than once per row.
     *
     * @return array<int, int>
     */
    private function getItemCounts(): array
    {
        /** @var array<int, int> */
        return $this->cachePerRender('itemCounts', function (): array {
            $user = $this->gatekeeperFactory->createGuiGatekeeper()->getUser();

            return ($user !== null) ? $this->playlistFolderRepository->getItemCounts($user) : [];
        });
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function parse(string $object): array
    {
        preg_match('/^([a-z_]+)-([0-9]+)$/', $object, $matches);

        return [$matches[1] ?? '', (int) ($matches[2] ?? 0)];
    }
}
