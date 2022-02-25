<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Gui\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
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

    public function getAlbumSuiteIds(): string
    {
        return count($this->album->album_suite) <= 1 ?
            $this->album->getId() :
            implode(',', $this->album->album_suite);
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
        $albumID = $this->album->getId();
        $name    = '[' . $this->album->f_album_artist_name . '] ' . scrub_out($this->album->get_fullname());

        $thumb = $this->browse->is_grid_view() ? 1 : 11;

        return Art::display_without_return('album', $albumID, $name, $thumb, $this->configContainer->getWebPath() . '/albums.php?action=show&album=' . $albumID);
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
            'play',
            T_('Play'),
            'play_album_' . $albumId
        );
    }

    public function getAutoplayNextButton(): string
    {
        $albumID = $this->album->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=album&object_id=' . $albumID . '&playnext=true',
            'play_next',
            T_('Play next'),
            'nextplay_album_' . $albumID
        );
    }

    public function getAppendNextButton(): string
    {
        $albumID = $this->album->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=album&object_id=' . $albumID . '&append=true',
            'play_add',
            T_('Play last'),
            'addplay_album_' . $albumID
        );
    }

    public function getAddToTemporaryPlaylistButton(): string
    {
        $albumID = $this->album->getId();

        return Ajax::button(
            '?action=basket&type=album_full&id=' . $albumID,
            'add',
            T_('Add to Temporary Playlist'),
            'add_album_' . $albumID
        );
    }

    public function getRandomToTemporaryPlaylistButton(): string
    {
        $albumID = $this->album->getId();

        return Ajax::button(
            '?action=basket&type=album_random&id=' . $albumID,
            'random',
            T_('Random to Temporary Playlist'),
            'random_album_' . $albumID
        );
    }

    public function canPostShout(): bool
    {
        return (
                $this->configContainer->isAuthenticationEnabled() === false ||
                $this->gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) === true
            ) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE) &&
            (!$this->album->allow_group_disks || ($this->album->allow_group_disks && count($this->album->album_suite) <= 1));
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
        return Ui::get_icon('comment', T_('Post Shout'));
    }

    public function canShare(): bool
    {
        return $this->gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE) &&
            (!$this->album->allow_group_disks || ($this->album->allow_group_disks && count($this->album->album_suite) <= 1));
    }

    public function getShareUi(): string
    {
        return Share::display_ui('album', $this->album->getId(), false);
    }

    public function canBatchDownload(): bool
    {
        return $this->functionChecker->check(AccessLevelEnum::FUNCTION_BATCH_DOWNLOAD) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD) &&
            $this->zipHandler->isZipable('album');
    }

    public function getBatchDownloadUrl(): string
    {
        return sprintf(
            '%s/batch.php?action=album&%s',
            $this->configContainer->getWebPath(),
            $this->album->get_http_album_query_ids('id')
        );
    }

    public function getBatchDownloadIcon(): string
    {
        return Ui::get_icon('batch_download', T_('Batch download'));
    }

    public function isEditable(): bool
    {
        return $this->gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER) &&
            (!$this->album->allow_group_disks || ($this->album->allow_group_disks && count($this->album->album_suite) <= 1));
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
            '%s/album.php?action=%s&album_id=%d',
            $this->configContainer->getWebPath(),
            DeleteAction::REQUEST_KEY,
            $this->album->getId()
        );
    }

    public function getDeletionIcon(): string
    {
        return Ui::get_icon('delete', T_('Delete'));
    }

    public function canBeDeleted(): bool
    {
        return Catalog::can_remove($this->album);
    }

    public function getAddToPlaylistIcon(): string
    {
        return Ui::get_icon('playlist_add', T_('Add to playlist'));
    }

    public function getPlayedTimes(): int
    {
        return $this->album->total_count;
    }

    public function getAlbumUrl(): string
    {
        return $this->album->get_link();
    }

    public function getAlbumLink(): string
    {
        return $this->album->get_f_link();
    }

    public function getArtistLink(): string
    {
        return !empty($this->album->f_album_artist_link) ? $this->album->f_album_artist_link : $this->album->f_artist_link;
    }

    public function canShowYear(): bool
    {
        return $this->album->year > 0;
    }

    public function getYear(): int
    {
        return $this->album->year;
    }

    public function getGenre(): string
    {
        return $this->album->f_tags;
    }

    public function getSongCount(): int
    {
        return $this->album->song_count;
    }
}
