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
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Folder;
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
        private Podcast_Episode|Video|Song|Folder $object,
        private string $object_type,
    ) {}

    public function canAppendNext(): bool
    {
        return Stream_Playlist::check_autoplay_append();
    }

    public function canAutoplayNext(): bool
    {
        return Stream_Playlist::check_autoplay_next();
    }

    public function canBatchDownload(): bool
    {
        return (
            $this->object_type === 'folder'
            && $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD)
            && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD)
            && $this->zipHandler->isZipable($this->object_type));
    }

    public function canBeDeleted(): bool
    {
        return Catalog::can_remove($this->object);
    }

    public function canDirectplay(): bool
    {
        if ($this->object instanceof Folder) {
            return (
                $this->object->playable
                && $this->object->object_count < $this->configContainer->get(ConfigurationKeyEnum::DIRECT_PLAY_LIMIT)
                && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DIRECTPLAY)
            );
        }

        return ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DIRECTPLAY));
    }

    public function canPostShout(): bool
    {
        return
            $this->object_type !== 'folder'
            && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE)
            && (
                $this->configContainer->isAuthenticationEnabled() === false
                || $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            );
    }

    public function canShare(): bool
    {
        return (
            $this->object_type !== 'folder'
            && $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)
        );
    }

    public function canShowYear(): bool
    {
        return $this->getDisplayYear() > 0;
    }

    public function getAddToPlaylistIcon(): string
    {
        return Ui::get_material_symbol('playlist_add', Ui::get_add_to_list_label());
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

    public function getArt(): string
    {
        $object_id   = $this->object->getId();
        $object_type = $this->object_type;
        if (
            $this->object_type === 'song'
            && $this->object instanceof Song
            && !$this->configContainer->get(ConfigurationKeyEnum::SHOW_SONG_ART)
        ) {
            $object_id   = $this->object->album;
            $object_type = 'album';
        }

        $name = scrub_out($this->object->get_fullname());
        $size = ['width' => 100, 'height' => 100];

        Art::display(
            $object_type,
            $object_id,
            $name,
            $size,
            $this->configContainer->getWebPath() . '/' . $object_type . 's.php?action=show&' . ($object_type === 'song' ? 'song_id' : $object_type) . '=' . $object_id
        );

        return '';
    }

    public function getArtistLink(): string
    {
        return $this->folder->get_link();
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

    public function getAverageRating(): string
    {
        $rating = $this->modelFactory->createRating(
            $this->object->getId(),
            $this->object_type
        );

        return (string) $rating->get_average_rating();
    }

    public function getBatchDownloadIcon(): string
    {
        return Ui::get_material_symbol('folder_zip', T_('Batch download'));
    }

    public function getBatchDownloadUrl(): string
    {
        return sprintf(
            '%s/batch.php?action=' . $this->object_type . '&id=%s',
            $this->configContainer->getWebPath(),
            $this->object->getId()
        );
    }

    public function getDeletionIcon(): string
    {
        return Ui::get_material_symbol('close', T_('Delete'));
    }

    public function getDeletionUrl(): string
    {
        $parent_id = ($this->object instanceof Folder && $this->object->parent !== null) ? $this->object->parent : -1;

        return sprintf(
            '%s/' . $this->object_type . 's.php?action=%s&' . $this->object_type . '_id=%d&parent_id=%d',
            $this->configContainer->getWebPath(),
            DeleteAction::REQUEST_KEY,
            $this->object->getId(),
            $parent_id
        );
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

    public function getDisplayYear(): int
    {
        return ((property_exists($this->object, 'original_year')) && $this->configContainer->get('use_original_year') && $this->object->original_year)
            ? $this->object->original_year ?? 0
            : (property_exists($this->object, 'year') ? $this->object->year : 0);
    }

    public function getEditButtonTitle(): string
    {
        return T_('Folder Edit');
    }

    public function getEditIcon(): string
    {
        return Ui::get_material_symbol('edit', T_('Edit'));
    }

    public function getFolderLink(): string
    {
        if (property_exists($this->object, 'file')) {
            return $this->object->get_f_link(pathinfo((string) $this->object->file, PATHINFO_BASENAME));
        }

        return $this->object->get_f_link(
            Folder::get_name_by_id($this->object->getId())
        );
    }

    public function getFolderUrl(): string
    {
        return $this->object->get_link();
    }

    public function getGenre(): string
    {
        return (method_exists($this->object, 'get_f_tags')) ? $this->object->get_f_tags() : '';
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getPlayedTimes(): ?int
    {
        return (property_exists($this->object, 'total_count') && (!property_exists($this->object, 'playable') || $this->object->playable))
            ? $this->object->total_count
            : null;
    }

    public function getPostShoutIcon(): string
    {
        return Ui::get_material_symbol('comment', T_('Post Shout'));
    }

    public function getPostShoutUrl(): string
    {
        return sprintf(
            '%s/shout.php?action=show_add_shout&type=' . $this->object_type . '&id=%d',
            $this->configContainer->getWebPath(),
            $this->object->getId()
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

    public function getRating(): string
    {
        return Rating::show($this->object->getId(), $this->object_type);
    }

    public function getShareUi(): string
    {
        return Share::display_ui($this->object_type, $this->object->getId(), false);
    }

    public function getSongCount(): ?int
    {
        return (property_exists($this->object, 'object_count'))
            ? $this->object->object_count
            : ((property_exists($this->object, 'song_count')) ? $this->object->song_count : null);
    }

    public function getUserFlags(): string
    {
        return Userflag::show($this->object->getId(), $this->object_type);
    }

    public function isEditable(): bool
    {
        return (
            $this->object_type !== 'folder'
            && (
                $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
                || $this->gatekeeper->getUserId() == $this->object->get_user_owner()
            )
        );
    }
}
