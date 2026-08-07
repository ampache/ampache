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

namespace Ampache\Gui\Collection;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Application\Collection\DeleteCollectionAction;
use Ampache\Module\Application\Collection\SetTrackNumbersAction;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Override;

final class CollectionViewAdapter extends AbstractView implements CollectionViewAdapterInterface
{
    private ?bool $playable = null;

    /**
     * @param array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}> $objectIds
     */
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly Collection $collection,
        private readonly ?User $user,
        private readonly array $objectIds,
    ) {}

    public function canDelete(): bool
    {
        return $this->collection->has_access($this->user);
    }

    public function canDirectPlay(): bool
    {
        return $this->isPlayable() && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DIRECTPLAY);
    }

    public function canEdit(): bool
    {
        return $this->collection->has_collaborate($this->user);
    }

    public function canPlayAppend(): bool
    {
        return $this->isPlayable() && Stream_Playlist::check_autoplay_append();
    }

    public function canPlayNext(): bool
    {
        return $this->isPlayable() && Stream_Playlist::check_autoplay_next();
    }

    public function canReorder(): bool
    {
        // Only a mixed collection is dragged here; a pinned one is shown through its own type's browse, which
        // has no drag handle and no order of its own to save
        return $this->canEdit() && $this->isMixed();
    }

    public function createBrowse(): Browse
    {
        $browse = $this->browseFactory->create();
        $browse->set_use_filters(false);
        $browse->set_static_content(true);

        return $browse;
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array
    {
        return Ui::is_grid_view('collection')
            ? ['width' => 150, 'height' => 150]
            : ['width' => 384, 'height' => 384];
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function getDeletionConfirmation(): string
    {
        return T_('Do you really want to delete this Collection?');
    }

    public function getDeletionIcon(): string
    {
        return Ui::get_material_symbol('close');
    }

    public function getDeletionUrl(): string
    {
        return sprintf(
            '%s/collection.php?action=%s&amp;collection=%d',
            $this->configContainer->getWebPath(),
            DeleteCollectionAction::REQUEST_KEY,
            $this->getId()
        );
    }

    public function getDirectplayButton(): string
    {
        return Ajax::button_with_text(
            '?page=stream&action=directplay&object_type=collection&object_id=' . $this->getId(),
            'play_circle',
            T_('Play All'),
            'directplay_full_' . $this->getId()
        );
    }

    public function getEditDialogTitle(): string
    {
        return addslashes(T_('Collection Edit'));
    }

    public function getEditIcon(): string
    {
        return Ui::get_material_symbol('edit', T_('Edit'));
    }

    public function getId(): int
    {
        return $this->collection->getId();
    }

    /**
     * @return list<int>
     */
    public function getMemberIds(): array
    {
        return array_column($this->objectIds, 'object_id');
    }

    public function getName(): string
    {
        return $this->collection->get_fullname() ?? '';
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}>
     */
    public function getObjectIds(): array
    {
        return $this->objectIds;
    }

    public function getOwner(): string
    {
        return $this->collection->username ?? '';
    }

    public function getPinnedBrowseType(): ?string
    {
        if ($this->isMixed()) {
            return null;
        }

        $type = Collection::normalizeType((string) $this->collection->object_type);
        // the `tag` browse is the tag cloud, which can neither take a list of ids nor keep the curated order
        if ($type === 'tag') {
            $type = 'genre';
        }

        return Browse::is_valid_type($type) ? $type : null;
    }

    public function getPlayLastButton(): string
    {
        return Ajax::button_with_text(
            '?page=stream&action=directplay&object_type=collection&object_id=' . $this->getId() . '&append=true',
            'low_priority',
            T_('Play All Last'),
            'addplay_collection_' . $this->getId()
        );
    }

    public function getPlayNextButton(): string
    {
        return Ajax::button_with_text(
            '?page=stream&action=directplay&object_type=collection&object_id=' . $this->getId() . '&playnext=true',
            'menu_open',
            T_('Play All Next'),
            'nextplay_collection_' . $this->getId()
        );
    }

    public function getRating(): string
    {
        return Rating::show($this->getId(), 'collection');
    }

    public function getReorderConfirmation(): string
    {
        return addslashes(T_('Save the current order of this collection?'));
    }

    public function getReorderIcon(): string
    {
        return Ui::get_material_symbol('save', T_('Save Track Order'));
    }

    public function getTrackNumbersUrl(): string
    {
        return sprintf(
            '%s/collection.php?action=%s&collection=%d',
            $this->configContainer->getWebPath(),
            SetTrackNumbersAction::REQUEST_KEY,
            $this->getId()
        );
    }

    public function getTypeLabel(): string
    {
        return ($this->isMixed())
            ? T_('Mixed')
            : (string) $this->collection->object_type;
    }

    public function getUserFlags(): string
    {
        return Userflag::show($this->getId(), 'collection');
    }

    public function isEmpty(): bool
    {
        return $this->objectIds === [];
    }

    public function isMixed(): bool
    {
        return $this->collection->object_type === null || $this->collection->object_type === '';
    }

    public function isPlayable(): bool
    {
        // The play buttons queue the expansion of the members; a collection of labels expands to nothing
        return $this->playable ??= $this->collection->get_medias() !== [];
    }

    public function isRatingsEnabled(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RATINGS) && User::is_registered();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('collection.phtml');
    }
}
