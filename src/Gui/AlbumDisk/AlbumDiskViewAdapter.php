<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Gui\AlbumDisk;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Application\Album\DeleteAction;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;

final class AlbumDiskViewAdapter implements AlbumDiskViewAdapterInterface
{
    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private ZipHandlerInterface $zipHandler;

    private FunctionCheckerInterface $functionChecker;

    private GuiGatekeeperInterface $gatekeeper;

    private Browse $browse;

    private AlbumDisk $albumDisk;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        ZipHandlerInterface $zipHandler,
        FunctionCheckerInterface $functionChecker,
        GuiGatekeeperInterface $gatekeeper,
        Browse $browse,
        AlbumDisk $albumDisk
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
        $this->zipHandler      = $zipHandler;
        $this->functionChecker = $functionChecker;
        $this->gatekeeper      = $gatekeeper;
        $this->browse          = $browse;
        $this->albumDisk       = $albumDisk;
    }

    public function getId(): int
    {
        return $this->albumDisk->getId();
    }

    public function getAlbumId(): int
    {
        return $this->albumDisk->getAlbumId();
    }

    public function getRating(): string
    {
        return Rating::show($this->albumDisk->getId(), 'album_disk');
    }

    public function getAverageRating(): string
    {
        $rating = $this->modelFactory->createRating(
            $this->albumDisk->getId(),
            'album_disk'
        );

        return (string) $rating->get_average_rating();
    }

    public function getUserFlags(): string
    {
        return Userflag::show($this->albumDisk->getId(), 'album_disk');
    }

    public function getArt(): string
    {
        $albumId = $this->albumDisk->getAlbumId();
        $name    = '[' . $this->albumDisk->get_album_artist_fullname() . '] ' . scrub_out($this->albumDisk->get_fullname());

        $thumb = $this->browse->is_grid_view() ? 1 : 11;

        return Art::display_without_return('album', $albumId, $name, $thumb, $this->configContainer->getWebPath() . '/albums.php?action=show&album=' . $albumId);
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
        $albumId = $this->albumDisk->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumId,
            'play',
            T_('Play'),
            'play_album_' . $albumId
        );
    }

    public function getAutoplayNextButton(): string
    {
        $albumId = $this->albumDisk->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumId . '&playnext=true',
            'play_next',
            T_('Play next'),
            'nextplay_album_' . $albumId
        );
    }

    public function getAppendNextButton(): string
    {
        $albumId = $this->albumDisk->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumId . '&append=true',
            'play_add',
            T_('Play last'),
            'addplay_album_' . $albumId
        );
    }

    public function getAddToTemporaryPlaylistButton(): string
    {
        $albumId = $this->albumDisk->getId();

        return Ajax::button(
            '?action=basket&type=album_disk&id=' . $albumId,
            'add',
            T_('Add to Temporary Playlist'),
            'add_album_' . $albumId
        );
    }

    public function getRandomToTemporaryPlaylistButton(): string
    {
        $albumId = $this->albumDisk->getId();

        return Ajax::button(
            '?action=basket&type=album_disk_random&id=' . $albumId,
            'random',
            T_('Random to Temporary Playlist'),
            'random_album_disk_' . $albumId
        );
    }

    public function canPostShout(): bool
    {
        return (
                $this->configContainer->isAuthenticationEnabled() === false ||
                $this->gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) === true
            ) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE);
    }

    public function getPostShoutUrl(): string
    {
        return sprintf(
            '%s/shout.php?action=show_add_shout&type=album_disk&id=%d',
            $this->configContainer->getWebPath(),
            $this->albumDisk->getId()
        );
    }

    public function getPostShoutIcon(): string
    {
        return Ui::get_icon('comment', T_('Post Shout'));
    }

    public function canShare(): bool
    {
        return $this->gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE);
    }

    public function getShareUi(): string
    {
        return Share::display_ui('album_disk', $this->albumDisk->getId(), false);
    }

    public function canBatchDownload(): bool
    {
        return $this->functionChecker->check(AccessLevelEnum::FUNCTION_BATCH_DOWNLOAD) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD) &&
            $this->zipHandler->isZipable('album_disk');
    }

    public function getBatchDownloadUrl(): string
    {
        return sprintf(
            '%s/batch.php?action=album_disk&id=%s',
            $this->configContainer->getWebPath(),
            $this->albumDisk->getId()
        );
    }

    public function getBatchDownloadIcon(): string
    {
        return Ui::get_icon('batch_download', T_('Batch download'));
    }

    public function isEditable(): bool
    {
        return $this->gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER);
    }

    public function getEditButtonTitle(): string
    {
        return T_('Album Edit');
    }

    public function getEditIcon(): string
    {
        return Ui::get_icon('edit', T_('Edit'));
    }

    public function getDeletionUrl(): string
    {
        return sprintf(
            '%s/albums.php?action=%s&album_id=%d',
            $this->configContainer->getWebPath(),
            DeleteAction::REQUEST_KEY,
            $this->albumDisk->getAlbumId()
        );
    }

    public function getDeletionIcon(): string
    {
        return Ui::get_icon('delete', T_('Delete'));
    }

    public function canBeDeleted(): bool
    {
        return Catalog::can_remove($this->modelFactory->createAlbum($this->albumDisk->getAlbumId()));
    }

    public function getAddToPlaylistIcon(): string
    {
        return Ui::get_icon('playlist_add', T_('Add to playlist'));
    }

    public function getPlayedTimes(): int
    {
        return $this->albumDisk->total_count;
    }

    public function getAlbumUrl(): string
    {
        return $this->albumDisk->get_link();
    }

    public function getAlbumLink(): string
    {
        return $this->albumDisk->get_f_link();
    }

    public function getArtistLink(): string
    {
        return !empty($this->albumDisk->get_f_album_artist_link()) ? $this->albumDisk->get_f_album_artist_link() : $this->albumDisk->get_f_artist_link();
    }

    public function canShowYear(): bool
    {
        return $this->getDisplayYear() > 0;
    }

    public function getDisplayYear(): int
    {
        return ($this->configContainer->get('use_original_year') && $this->albumDisk->original_year)
            ? $this->albumDisk->original_year
            : $this->albumDisk->year;
    }

    public function getGenre(): string
    {
        return $this->albumDisk->f_tags;
    }

    public function getSongCount(): int
    {
        return $this->albumDisk->song_count;
    }
}
