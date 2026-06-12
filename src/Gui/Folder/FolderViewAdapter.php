<?php

declare(strict_types=0);

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

namespace Ampache\Gui\Folder;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Application\Folder\DeleteAction;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Userflag;

final readonly class FolderViewAdapter implements FolderViewAdapterInterface
{
    public function __construct(
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
        private ZipHandlerInterface $zipHandler,
        private FunctionCheckerInterface $functionChecker,
        private GuiGatekeeperInterface $gatekeeper,
        private Browse $browse,
        private Folder $folder,
    ) {
    }

    public function getId(): int
    {
        return $this->folder->getId();
    }

    public function getRating(): string
    {
        return Rating::show($this->folder->getId(), 'folder');
    }

    public function getAverageRating(): string
    {
        $rating = $this->modelFactory->createRating(
            $this->folder->getId(),
            'folder'
        );

        return (string) $rating->get_average_rating();
    }

    public function getUserFlags(): string
    {
        return Userflag::show($this->folder->getId(), 'folder');
    }

    public function getArt(): string
    {
        $folderId = $this->folder->getId();
        $name    = scrub_out($this->folder->get_fullname());

        $size = ($this->browse->is_grid_view())
            ? ['width' => 150, 'height' => 150]
            : ['width' => 100, 'height' => 100];

        Art::display(
            'folder',
            $folderId,
            $name,
            $size,
            $this->configContainer->getWebPath() . '/folders.php?action=show&folder=' . $folderId
        );

        return '';
    }

    public function canAutoplayNext(): bool
    {
        return Stream_Playlist::check_autoplay_next();
    }

    public function canAppendNext(): bool
    {
        return Stream_Playlist::check_autoplay_append();
    }

    public function getDirectplayButton(): string
    {
        $folderId = $this->folder->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=folder&object_id=' . $folderId,
            'play_circle',
            T_('Play'),
            'play_folder_' . $folderId
        );
    }

    public function getAutoplayNextButton(): string
    {
        $folderId = $this->folder->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=folder&object_id=' . $folderId . '&playnext=true',
            'menu_open',
            T_('Play next'),
            'nextplay_folder_' . $folderId
        );
    }

    public function getAppendNextButton(): string
    {
        $folderId = $this->folder->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=folder&object_id=' . $folderId . '&append=true',
            'low_priority',
            T_('Play last'),
            'addplay_folder_' . $folderId
        );
    }

    public function getAddToTemporaryPlaylistButton(): string
    {
        $folderId = $this->folder->getId();

        return Ajax::button(
            '?action=basket&type=folder&id=' . $folderId,
            'new_window',
            T_('Add to Temporary Playlist'),
            'add_folder_' . $folderId
        );
    }

    public function getRandomToTemporaryPlaylistButton(): string
    {
        $folderId = $this->folder->getId();

        return Ajax::button(
            '?action=basket&type=folder_random&id=' . $folderId,
            'shuffle',
            T_('Random to Temporary Playlist'),
            'random_folder_' . $folderId
        );
    }

    public function canPostShout(): bool
    {
        return (
            $this->configContainer->isAuthenticationEnabled() === false ||
            $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
        ) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE);
    }

    public function getPostShoutUrl(): string
    {
        return sprintf(
            '%s/shout.php?action=show_add_shout&type=folder&id=%d',
            $this->configContainer->getWebPath(),
            $this->folder->getId()
        );
    }

    public function getPostShoutIcon(): string
    {
        return Ui::get_material_symbol('comment', T_('Post Shout'));
    }

    public function canShare(): bool
    {
        return $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE);
    }

    public function getShareUi(): string
    {
        return Share::display_ui('folder', $this->folder->getId(), false);
    }

    public function canBatchDownload(): bool
    {
        return $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD) &&
            $this->zipHandler->isZipable('folder');
    }

    public function getBatchDownloadUrl(): string
    {
        return sprintf(
            '%s/batch.php?action=folder&id=%s',
            $this->configContainer->getWebPath(),
            $this->folder->id
        );
    }

    public function getBatchDownloadIcon(): string
    {
        return Ui::get_material_symbol('folder_zip', T_('Batch download'));
    }

    public function isEditable(): bool
    {
        return (
            $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) ||
            $this->gatekeeper->getUserId() == $this->folder->get_user_owner()
        );
    }

    public function getEditButtonTitle(): string
    {
        return T_('Folder Edit');
    }

    public function getEditIcon(): string
    {
        return Ui::get_material_symbol('edit', T_('Edit'));
    }

    public function getDeletionUrl(): string
    {
        return sprintf(
            '%s/folders.php?action=%s&folder_id=%d',
            $this->configContainer->getWebPath(),
            DeleteAction::REQUEST_KEY,
            $this->folder->getId()
        );
    }

    public function getDeletionIcon(): string
    {
        return Ui::get_material_symbol('close', T_('Delete'));
    }

    public function canBeDeleted(): bool
    {
        return Catalog::can_remove($this->folder);
    }

    public function getAddToPlaylistIcon(): string
    {
        return Ui::get_material_symbol('playlist_add', T_('Add to playlist'));
    }

    public function getPlayedTimes(): int
    {
        return $this->folder->total_count;
    }

    public function getFolderUrl(): string
    {
        return $this->folder->get_link();
    }

    public function getFolderLink(): string
    {
        return $this->folder->get_f_link();
    }

    public function getArtistLink(): string
    {
        return (string)$this->folder->get_f_parent_link();
    }

    public function canShowYear(): bool
    {
        return $this->getDisplayYear() > 0;
    }

    public function getDisplayYear(): int
    {
        return ($this->configContainer->get('use_original_year') && $this->folder->original_year)
            ? $this->folder->original_year
            : $this->folder->year;
    }

    public function getGenre(): string
    {
        return $this->folder->get_f_tags();
    }

    public function getSongCount(): int
    {
        return $this->folder->song_count;
    }
}
