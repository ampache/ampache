<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Gui\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
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

final class AlbumViewAdapter implements AlbumViewAdapterInterface
{
    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private ZipHandlerInterface $zipHandler;

    private FunctionCheckerInterface $functionChecker;

    private GuiGatekeeperInterface $gatekeeper;

    private Browse $browse;

    private Album $album;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        ZipHandlerInterface $zipHandler,
        FunctionCheckerInterface $functionChecker,
        GuiGatekeeperInterface $gatekeeper,
        Browse $browse,
        Album $album
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
        $this->zipHandler      = $zipHandler;
        $this->functionChecker = $functionChecker;
        $this->gatekeeper      = $gatekeeper;
        $this->browse          = $browse;
        $this->album           = $album;
    }

    public function getId(): int
    {
        return $this->album->getId();
    }

    public function getRating(): string
    {
        return Rating::show($this->album->getId(), 'album');
    }

    public function getAverageRating(): string
    {
        $rating = $this->modelFactory->createRating(
            $this->album->getId(),
            'album'
        );

        return (string) $rating->get_average_rating();
    }

    public function getUserFlags(): string
    {
        return Userflag::show($this->album->getId(), 'album');
    }

    public function getArt(): string
    {
        $albumId = $this->album->getId();
        $name    = ($this->album->get_artist_fullname() != "")
            ? '[' . $this->album->get_artist_fullname() . '] ' . scrub_out($this->album->get_fullname())
            : scrub_out($this->album->get_fullname());

        $thumb = $this->browse->is_grid_view() ? 1 : 11;

        Art::display('album', $albumId, $name, $thumb, $this->configContainer->getWebPath() . '/albums.php?action=show&album=' . $albumId);

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
        $albumId = $this->album->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=album&object_id=' . $albumId,
            'play_circle',
            T_('Play'),
            'play_album_' . $albumId
        );
    }

    public function getAutoplayNextButton(): string
    {
        $albumId = $this->album->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=album&object_id=' . $albumId . '&playnext=true',
            'menu_open',
            T_('Play next'),
            'nextplay_album_' . $albumId
        );
    }

    public function getAppendNextButton(): string
    {
        $albumId = $this->album->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=album&object_id=' . $albumId . '&append=true',
            'low_priority',
            T_('Play last'),
            'addplay_album_' . $albumId
        );
    }

    public function getAddToTemporaryPlaylistButton(): string
    {
        $albumId = $this->album->getId();

        return Ajax::button(
            '?action=basket&type=album&id=' . $albumId,
            'new_window',
            T_('Add to Temporary Playlist'),
            'add_album_' . $albumId
        );
    }

    public function getRandomToTemporaryPlaylistButton(): string
    {
        $albumId = $this->album->getId();

        return Ajax::button(
            '?action=basket&type=album_random&id=' . $albumId,
            'shuffle',
            T_('Random to Temporary Playlist'),
            'random_album_' . $albumId
        );
    }

    public function canPostShout(): bool
    {
        return (
            $this->configContainer->isAuthenticationEnabled() === false ||
            $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === true
        ) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE);
    }

    public function getPostShoutUrl(): string
    {
        return sprintf(
            '%s/shout.php?action=show_add_shout&type=album&id=%d',
            $this->configContainer->getWebPath(),
            $this->album->getId()
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
        return Share::display_ui('album', $this->album->getId(), false);
    }

    public function canBatchDownload(): bool
    {
        return $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD) &&
            $this->zipHandler->isZipable('album');
    }

    public function getBatchDownloadUrl(): string
    {
        return sprintf(
            '%s/batch.php?action=album&id=%s',
            $this->configContainer->getWebPath(),
            $this->album->id
        );
    }

    public function getBatchDownloadIcon(): string
    {
        return Ui::get_material_symbol('folder_zip', T_('Batch download'));
    }

    public function isEditable(): bool
    {
        return ($this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) || $this->gatekeeper->getUserId() == $this->album->get_user_owner());
    }

    public function getEditButtonTitle(): string
    {
        return T_('Album Edit');
    }

    public function getEditIcon(): string
    {
        return Ui::get_material_symbol('edit', T_('Edit'));
    }

    public function getDeletionUrl(): string
    {
        return sprintf(
            '%s/albums.php?action=%s&album_id=%d',
            $this->configContainer->getWebPath(),
            DeleteAction::REQUEST_KEY,
            $this->album->getId()
        );
    }

    public function getDeletionIcon(): string
    {
        return Ui::get_material_symbol('close', T_('Delete'));
    }

    public function canBeDeleted(): bool
    {
        return Catalog::can_remove($this->album);
    }

    public function getAddToPlaylistIcon(): string
    {
        return Ui::get_material_symbol('playlist_add', T_('Add to playlist'));
    }

    public function getPlayedTimes(): int
    {
        return $this->album->total_count;
    }

    public function getAlbumUrl(): string
    {
        return (string)$this->album->get_link();
    }

    public function getAlbumLink(): string
    {
        return (string)$this->album->get_f_link();
    }

    public function getArtistLink(): string
    {
        return (string)$this->album->get_f_artist_link();
    }

    public function canShowYear(): bool
    {
        return $this->getDisplayYear() > 0;
    }

    public function getDisplayYear(): int
    {
        return ($this->configContainer->get('use_original_year') && $this->album->original_year)
            ? $this->album->original_year
            : $this->album->year;
    }

    public function getGenre(): string
    {
        return (string)$this->album->f_tags;
    }

    public function getSongCount(): int
    {
        return $this->album->song_count;
    }
}
