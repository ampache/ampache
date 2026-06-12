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
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;

final readonly class FolderViewAdapter implements FolderViewAdapterInterface
{
    public function __construct(
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
        private ZipHandlerInterface $zipHandler,
        private FunctionCheckerInterface $functionChecker,
        private GuiGatekeeperInterface $gatekeeper,
        private Folder $folder,
        private Podcast_Episode|AlbumDisk|Video|Song|Album|Artist|Label|Folder $object,
        private string $object_type,
    ) {
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getRating(): string
    {
        return Rating::show($this->object->getId(), $this->object_type);
    }

    public function getAverageRating(): string
    {
        $rating = $this->modelFactory->createRating(
            $this->object->getId(),
            $this->object_type
        );

        return (string) $rating->get_average_rating();
    }

    public function getUserFlags(): string
    {
        return Userflag::show($this->object->getId(), $this->object_type);
    }

    public function getArt(): string
    {
        $object_id   = $this->object->getId();
        $object_type = $this->object_type;
        $name        = scrub_out($this->object->get_fullname());
        $size        = ['width' => 100, 'height' => 100];

        Art::display(
            $object_type,
            $object_id,
            $name,
            $size,
            $this->configContainer->getWebPath() . '/' . $object_type . 's.php?action=show&' . ($object_type === 'song' ? 'song_id' : $object_type) . '=' . $object_id
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
        $object_id   = $this->object->getId();
        $object_type = $this->object_type;

        return Ajax::button(
            '?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $object_id,
            'play_circle',
            T_('Play'),
            'play_' . $object_type . '_' . $object_id
        );
    }

    public function getAutoplayNextButton(): string
    {
        $object_id   = $this->object->getId();
        $object_type = $this->object_type;

        return Ajax::button(
            '?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $object_id . '&playnext=true',
            'menu_open',
            T_('Play next'),
            'nextplay_' . $object_type . '_' . $object_id
        );
    }

    public function getAppendNextButton(): string
    {
        $object_id   = $this->object->getId();
        $object_type = $this->object_type;

        return Ajax::button(
            '?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $object_id . '&append=true',
            'low_priority',
            T_('Play last'),
            'addplay_' . $object_type . '_' . $object_id
        );
    }

    public function getAddToTemporaryPlaylistButton(): string
    {
        $object_id   = $this->object->getId();
        $object_type = $this->object_type;

        return Ajax::button(
            '?action=basket&type=' . $object_type . '&id=' . $object_id,
            'new_window',
            T_('Add to Temporary Playlist'),
            'add_' . $object_type . '_' . $object_id
        );
    }

    public function getRandomToTemporaryPlaylistButton(): string
    {
        $object_id   = $this->object->getId();
        $object_type = $this->object_type;

        return Ajax::button(
            '?action=basket&type=' . $object_type . '_random&id=' . $object_id,
            'shuffle',
            T_('Random to Temporary Playlist'),
            'random_' . $object_type . '_' . $object_id
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
            '%s/shout.php?action=show_add_shout&type=' . $this->object_type . '&id=%d',
            $this->configContainer->getWebPath(),
            $this->object->getId()
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
        return Share::display_ui($this->object_type, $this->object->getId(), false);
    }

    public function canBatchDownload(): bool
    {
        return $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD) &&
            $this->zipHandler->isZipable($this->object_type);
    }

    public function getBatchDownloadUrl(): string
    {
        return sprintf(
            '%s/batch.php?action=' . $this->object_type . '&id=%s',
            $this->configContainer->getWebPath(),
            $this->object->getId()
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
            $this->gatekeeper->getUserId() == $this->object->get_user_owner()
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
            '%s/' . $this->object_type . 's.php?action=%s&' . ($this->object_type === 'song' ? 'song_id' : $this->object_type) . '=%d',
            $this->configContainer->getWebPath(),
            DeleteAction::REQUEST_KEY,
            $this->object->getId()
        );
    }

    public function getDeletionIcon(): string
    {
        return Ui::get_material_symbol('close', T_('Delete'));
    }

    public function canBeDeleted(): bool
    {
        return Catalog::can_remove($this->object);
    }

    public function getAddToPlaylistIcon(): string
    {
        return Ui::get_material_symbol('playlist_add', T_('Add to playlist'));
    }

    public function getPlayedTimes(): int
    {
        return (property_exists($this->object, 'total_count')) ? $this->object->total_count : 0;
    }

    public function getFolderUrl(): string
    {
        return $this->object->get_link();
    }

    public function getFolderLink(): string
    {
        return $this->object->get_f_link();
    }

    public function getArtistLink(): string
    {
        return (string)$this->folder->get_link();
    }

    public function canShowYear(): bool
    {
        return $this->getDisplayYear() > 0;
    }

    public function getDisplayYear(): int
    {
        return ((property_exists($this->object, 'original_year')) && $this->configContainer->get('use_original_year') && $this->object->original_year)
            ? $this->object->original_year ?? 0
            : (property_exists($this->object, 'year') ? $this->object->year : 0);
    }

    public function getGenre(): string
    {
        return (method_exists($this->object, 'get_f_tags')) ? $this->object->get_f_tags() : '';
    }

    public function getSongCount(): int
    {
        return (property_exists($this->object, 'object_count'))
            ? $this->object->object_count
            : ((property_exists($this->object, 'song_count')) ? $this->object->song_count : 0);
    }
}
