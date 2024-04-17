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

namespace Ampache\Gui\Playlist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;

final class PlaylistViewAdapter implements PlaylistViewAdapterInterface
{
    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private ZipHandlerInterface $zipHandler;

    private FunctionCheckerInterface $functionChecker;

    private GuiGatekeeperInterface $gatekeeper;

    private Playlist $playlist;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        ZipHandlerInterface $zipHandler,
        FunctionCheckerInterface $functionChecker,
        GuiGatekeeperInterface $gatekeeper,
        Playlist $playlist
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
        $this->zipHandler      = $zipHandler;
        $this->functionChecker = $functionChecker;
        $this->gatekeeper      = $gatekeeper;
        $this->playlist        = $playlist;
    }

    public function getId(): int
    {
        return $this->playlist->getId();
    }

    public function getRating(): string
    {
        return Rating::show($this->playlist->getId(), 'playlist');
    }

    public function getAverageRating(): string
    {
        $rating = $this->modelFactory->createRating(
            $this->playlist->getId(),
            'playlist'
        );

        return (string) $rating->get_average_rating();
    }

    public function getUserFlags(): string
    {
        return Userflag::show($this->playlist->getId(), 'playlist');
    }

    public function getArt(): void
    {
        $this->playlist->display_art(2, true);
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
        $playlistId = $this->playlist->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=playlist&object_id=' . $playlistId,
            'play_circle',
            T_('Play'),
            'play_playlist_' . $playlistId
        );
    }

    public function getAutoplayNextButton(): string
    {
        $playlistId = $this->playlist->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=playlist&object_id=' . $playlistId . '&playnext=true',
            'menu_open',
            T_('Play next'),
            'nextplay_playlist_' . $playlistId
        );
    }

    public function getAppendNextButton(): string
    {
        $playlistId = $this->playlist->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=playlist&object_id=' . $playlistId . '&append=true',
            'playlist_add',
            T_('Play last'),
            'addplay_playlist_' . $playlistId
        );
    }

    public function getAddToTemporaryPlaylistButton(): string
    {
        $playlistId = $this->playlist->getId();

        return Ajax::button(
            '?action=basket&type=playlist&id=' . $playlistId,
            'new_window',
            T_('Add to Temporary Playlist'),
            'add_playlist_' . $playlistId
        );
    }

    public function getRandomToTemporaryPlaylistButton(): string
    {
        $playlistId = $this->playlist->getId();

        return Ajax::button(
            '?action=basket&type=playlist_random&id=' . $playlistId,
            'random',
            T_('Random to Temporary Playlist'),
            'random_playlist_' . $playlistId
        );
    }

    public function canShare(): bool
    {
        return $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE);
    }

    public function getShareUi(): string
    {
        return Share::display_ui('playlist', $this->playlist->getId(), false);
    }

    public function canBatchDownload(): bool
    {
        return $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD) &&
            $this->zipHandler->isZipable('playlist');
    }

    public function getBatchDownloadUrl(): string
    {
        return sprintf(
            '%s/batch.php?action=playlist&id=%d',
            $this->configContainer->getWebPath(),
            $this->playlist->getId()
        );
    }

    public function getBatchDownloadIcon(): string
    {
        return Ui::get_material_symbol('folder_zip', T_('Batch download'));
    }

    public function isEditable(): bool
    {
        return $this->playlist->has_access();
    }

    public function getEditButtonTitle(): string
    {
        return T_('Playlist Edit');
    }

    public function getEditIcon(): string
    {
        return Ui::get_material_symbol('edit', T_('Edit'));
    }

    public function canBeDeleted(): bool
    {
        return $this->playlist->has_access();
    }

    public function getDeletionUrl(): string
    {
        return sprintf(
            '?page=browse&action=delete_object&type=playlist&id=%d',
            $this->playlist->getId()
        );
    }

    public function getDeletionButton(): string
    {
        $playlistId = $this->playlist->getId();

        return Ajax::button(
            $this->getDeletionUrl(),
            'delete',
            T_('Delete'),
            'delete_playlist_' . $playlistId,
            '',
            '',
            T_('Do you really want to delete this Playlist?')
        );
    }

    public function canBeRefreshed(): bool
    {
        $search_id = $this->playlist->has_search((int)$this->playlist->user);

        return $this->playlist->has_access() &&
            $search_id > 0;
    }

    public function getRefreshUrl(): string
    {
        $search_id = $this->playlist->has_search((int)$this->playlist->user);

        return sprintf(
            '%s/playlist.php?action=refresh_playlist&type=playlist&user_id=%d&playlist_id=%d&search_id=%d',
            $this->configContainer->getWebPath(),
            $this->playlist->user,
            $this->playlist->getId(),
            $search_id
        );
    }

    public function getRefreshIcon(): string
    {
        return Ui::get_material_symbol('sync_alt', T_('Refresh from Smartlist'));
    }

    public function getAddToPlaylistIcon(): string
    {
        return Ui::get_material_symbol('playlist_add', T_('Add to playlist'));
    }

    public function getPlaylistUrl(): string
    {
        return (string)$this->playlist->get_link();
    }

    public function getPlaylistLink(): string
    {
        return (string)$this->playlist->get_f_link();
    }

    public function getUsername(): string
    {
        return (string)$this->playlist->username;
    }

    public function getLastUpdate(): string
    {
        return $this->playlist->f_last_update ?? '';
    }

    public function getType(): string
    {
        return $this->playlist->get_f_type();
    }

    public function getMediaCount(): int
    {
        return $this->playlist->get_media_count();
    }
}
