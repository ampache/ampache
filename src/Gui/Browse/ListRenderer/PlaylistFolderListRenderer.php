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
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\playlist_object;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
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
        private readonly UserRepositoryInterface $userRepository,
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
        $columns = [
            ['class' => 'cel_play essential', 'label' => '', 'footer' => false],
            ['class' => 'cel_cover essential', 'label' => T_('Art'), 'footer' => false],
            ['class' => 'cel_name essential persist', 'label' => T_('Name'), 'footer' => false],
            ['class' => 'cel_add_list essential', 'label' => '', 'footer' => false],
            ['class' => 'cel_last_update optional', 'label' => T_('Last Update'), 'footer' => false],
            ['class' => 'cel_type optional', 'label' => T_('Type'), 'footer' => false],
            ['class' => 'cel_time optional', 'label' => T_('Time'), 'footer' => false],
            ['class' => 'cel_count optional', 'label' => T_('# Items'), 'footer' => false],
        ];

        if ($this->showRatings()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'footer' => false];
        }

        $columns[] = ['class' => 'cel_owner essential', 'label' => T_('Owner'), 'footer' => false];
        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Actions'), 'footer' => false];
        $columns[] = ['class' => 'cel_drag essential', 'label' => '', 'footer' => false];

        return $columns;
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

    /**
     * Folders the viewer has hidden from their own root; hiding only de-emphasises a folder, so this never
     * removes it from the listing -- it is used to mark the row and to decide which toggle action to show.
     *
     * @return list<PlaylistFolder>
     */
    public function getHiddenFolders(): array
    {
        return $this->cachePerRender('hiddenFolders', function (): array {
            $user = $this->gatekeeperFactory->createGuiGatekeeper()->getUser();

            return ($user !== null) ? $this->playlistFolderRepository->getHiddenFolders($user->getId()) : [];
        });
    }

    public function getHideFolderUrl(int $folderId): string
    {
        return $this->configContainer->getWebPath() . '/playlist_folder.php?action=hide&folder=' . $folderId;
    }

    /**
     * The Add cell the standalone playlist/smart-playlist browses show for this item; a folder is not
     * addable to a playlist itself, so it gets none.
     */
    public function getRowAdd(PlaylistFolder|playlist_object $item): string
    {
        if (!$this->mayCreate()) {
            return '';
        }

        if ($item instanceof Playlist) {
            $playlist   = $this->guiFactory->createPlaylistViewAdapter($this->gatekeeperFactory->createGuiGatekeeper(), $item);
            $playlistId = $playlist->getId();

            return $playlist->getRandomPlayPlaylistButton()
                . $playlist->getAddToTemporaryPlaylistButton()
                . $playlist->getRandomToTemporaryPlaylistButton()
                . '<a id="add_to_playlist_' . $playlistId . '" onclick="showPlaylistDialog(event, \'playlist\', \'' . $playlistId . '\')">' . $playlist->getAddToPlaylistIcon() . '</a>';
        }

        if ($item instanceof Search) {
            $searchId = $item->id;

            return Ajax::button('?page=random&action=send_playlist&random_type=search&random_id=' . $searchId, 'autorenew', T_('Random Play'), 'play_random_' . $searchId)
                . Ajax::button('?action=basket&type=search&id=' . $searchId, 'new_window', T_('Add to Temporary Playlist'), 'add_playlist_' . $searchId)
                . '<a id="add_to_playlist_' . $searchId . '" onclick="showPlaylistDialog(event, \'search\', \'' . $searchId . '\')">' . Ui::get_material_symbol('playlist_add', Ui::get_add_to_list_label()) . '</a>';
        }

        return '';
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
            $name = $this->e($item->getName());
            if ($this->isHiddenForViewer($item)) {
                $name .= ' <span class="playlist-folder-hidden-marker" title="' . $this->e(T_('Hidden from your root view')) . '">*</span>';
            }

            return '<a href="' . $this->e($this->getFolderUrl($item->getId())) . '">' . $name . '</a>';
        }

        return $item->get_f_link();
    }

    public function getRowOwner(PlaylistFolder|playlist_object $item): string
    {
        if (!$item instanceof PlaylistFolder) {
            return (string) $item->username;
        }

        if ($this->isOwnFolder($item)) {
            return '';
        }

        $owner = $this->userRepository->findById($item->getUserId());

        return ($owner !== null) ? sprintf(T_('Shared by %s'), $owner->getUsername()) : '';
    }

    /**
     * The Play cell the standalone playlist/smart-playlist browses show for this item; a folder is not
     * playable, so it gets none.
     */
    public function getRowPlay(PlaylistFolder|playlist_object $item): string
    {
        if (!$this->isDirectplayEnabled()) {
            return '';
        }

        if ($item instanceof Playlist) {
            $playlist = $this->guiFactory->createPlaylistViewAdapter($this->gatekeeperFactory->createGuiGatekeeper(), $item);
            $html     = $playlist->getDirectplayButton();

            if ($playlist->canAutoplayNext()) {
                $html .= $playlist->getAutoplayNextButton();
            }

            if ($playlist->canAppendNext()) {
                $html .= $playlist->getAppendNextButton();
            }

            return $html;
        }

        if ($item instanceof Search) {
            $searchId = $item->id;
            $html     = Ajax::button('?page=stream&action=directplay&object_type=search&object_id=' . $searchId, 'play_circle', T_('Play'), 'play_playlist_' . $searchId);

            if (Stream_Playlist::check_autoplay_next()) {
                $html .= Ajax::button('?page=stream&action=directplay&object_type=search&object_id=' . $searchId . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_playlist_' . $searchId);
            }

            if (Stream_Playlist::check_autoplay_append()) {
                $html .= Ajax::button('?page=stream&action=directplay&object_type=search&object_id=' . $searchId . '&append=true', 'low_priority', T_('Play last'), 'addplay_playlist_' . $searchId);
            }

            return $html;
        }

        return '';
    }

    /**
     * The Rating cell the standalone playlist/smart-playlist browses show for this item; a folder carries
     * no rating of its own.
     */
    public function getRowRatings(PlaylistFolder|playlist_object $item): string
    {
        if ($item instanceof Playlist) {
            $playlist   = $this->guiFactory->createPlaylistViewAdapter($this->gatekeeperFactory->createGuiGatekeeper(), $item);
            $playlistId = $playlist->getId();

            return '<span class="cel_rating" id="rating_' . $playlistId . '_playlist">' . $playlist->getRating() . '</span>'
                . '<span class="cel_userflag" id="userflag_' . $playlistId . '_playlist">' . $playlist->getUserFlags() . '</span>';
        }

        if ($item instanceof Search) {
            $searchId = $item->id;

            return '<span class="cel_rating" id="rating_' . $searchId . '_search">' . Rating::show($searchId, 'search') . '</span>'
                . '<span class="cel_userflag" id="userflag_' . $searchId . '_search">' . Userflag::show($searchId, 'search') . '</span>';
        }

        return '';
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

    public function getRowTime(PlaylistFolder|playlist_object $item): string
    {
        return ($item instanceof PlaylistFolder) ? '' : $item->get_f_time();
    }

    /**
     * The drag row id for one row, in the same `<type>-<id>` encoding `PlaylistFolderAction` builds its
     * ids from, so `SetSortOrderAction` can parse a dragged order back into per-row saves.
     */
    public function getRowTrackId(string $type, PlaylistFolder|playlist_object $item): string
    {
        return sprintf('%s-%d', $type, $item->getId());
    }

    public function getRowType(PlaylistFolder|playlist_object $item): string
    {
        if ($item instanceof playlist_object && $item->isPrivate()) {
            return Ui::get_material_symbol('lock', T_('Private'));
        }

        return '';
    }

    /**
     * The `set_sort_order` URL a drag-to-reorder save posts to for the folder currently being browsed.
     */
    public function getSetSortOrderUrl(): string
    {
        return $this->configContainer->getWebPath() . '/playlist_folder.php?action=set_sort_order&folder=' . $this->getCurrentFolderId();
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

    public function getUnhideFolderUrl(int $folderId): string
    {
        return $this->configContainer->getWebPath() . '/playlist_folder.php?action=unhide&folder=' . $folderId;
    }

    public function isDirectplayEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DIRECTPLAY);
    }

    /**
     * Whether the viewer dismissed this folder -- their own or one shared with them -- from their own
     * root; it stays usable, only marked
     */
    public function isHiddenForViewer(PlaylistFolder $folder): bool
    {
        foreach ($this->getHiddenFolders() as $hidden) {
            if ($hidden->getId() === $folder->getId()) {
                return true;
            }
        }

        return false;
    }

    public function isOwnFolder(PlaylistFolder $folder): bool
    {
        return $folder->isVisible($this->gatekeeperFactory->createGuiGatekeeper()->getUser());
    }

    /**
     * False for a folder shared by another user, so the toolbar and per-row drag/edit controls stay hidden.
     */
    public function mayCreate(): bool
    {
        $folder = $this->getCurrentFolder();

        return $this->gatekeeperFactory->createGuiGatekeeper()->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            && ($folder === null || $this->isOwnFolder($folder));
    }

    /**
     * The Hide/Unhide action cell for a root-level folder, own or shared; toggles on the current mark
     */
    public function renderHideToggle(PlaylistFolder $folder): string
    {
        if ($this->isHiddenForViewer($folder)) {
            return '<a href="' . $this->e($this->getUnhideFolderUrl($folder->getId())) . '">'
                . Ui::get_material_symbol('visibility', T_('Unhide'))
                . '</a>';
        }

        return '<a href="' . $this->e($this->getHideFolderUrl($folder->getId())) . '">'
            . Ui::get_material_symbol('visibility_off', T_('Hide'))
            . '</a>';
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
     * Echoes this row's cover art -- a subfolder has none of its own, so it always shows the shared folder
     * placeholder; a playlist or smartlist shows its own real art via the same `display_art()` every other
     * playlist/smartlist view uses. Never resolves a folder id through `Art::display('folder', ...)`: that
     * object_type is the real catalog folder feature's id space, and a lookup there could collide with it.
     */
    public function renderRowArt(PlaylistFolder|playlist_object $item): void
    {
        if ($item instanceof PlaylistFolder) {
            $name = $this->e($item->getName());
            echo '<div class="item_art"><img src="' . $this->e(Art::get_fallback_url('folder', '100x100')) . '" title="' . $name . '" alt="' . $name . '" height="100" width="100" /></div>';

            return;
        }

        $item->display_art(['width' => 100, 'height' => 100], true);
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

        if ($item->mayOpenEditDialog()) {
            $title = addslashes(T_('Smart Playlist Edit'));
            // Empty refresh prefix reloads the whole browse instead of one row -- a folder change moves the row out of view entirely
            $html .= '<a id="edit_playlist_' . $searchId . '" onclick="showEditDialog(\'search_row\', \'' . $searchId . '\', \'edit_playlist_' . $searchId . '\', \'' . $title . '\', \'\')">' . Ui::get_material_symbol('edit', T_('Edit')) . '</a>';
        }

        // Deletion stays owner/admin only -- mayOpenEditDialog() above also admits a public list's viewer, who may not delete it
        if ($item->has_access()) {
            $html .= Ajax::button('?page=browse&action=delete_object&type=smartplaylist&id=' . $searchId, 'close', T_('Delete'), 'delete_playlist_' . $searchId, '', '', T_('Are You Sure?'));
        }

        return $html;
    }

    public function showRatings(): bool
    {
        return User::is_registered() && $this->configContainer->get('ratings');
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
            if ($user === null) {
                return [];
            }

            $counts        = $this->playlistFolderRepository->getItemCounts($user->getId());
            $foreignOwners = [];
            foreach ($this->getRows() as $row) {
                if ($row['item'] instanceof PlaylistFolder && !$this->isOwnFolder($row['item'])) {
                    $foreignOwners[$row['item']->getUserId()] = true;
                }
            }

            foreach (array_keys($foreignOwners) as $ownerId) {
                $counts += $this->playlistFolderRepository->getItemCounts($ownerId);
            }

            return $counts;
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
