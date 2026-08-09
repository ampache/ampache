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
use Ampache\Gui\Collection\CollectionItemRowView;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\displayable_item;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\User;
use Override;

/**
 * The collection contents browse, reached only by a mixed collection; a pinned one goes to its own type.
 *
 * A member the viewer may not see used to open a row and emit no cells at all.
 */
final class CollectionItemsListRenderer extends AbstractBrowseListRenderer
{
    /**
     * @var list<array{item: displayable_item&library_item, type: string, trackId: int, track: int}>|null
     */
    private ?array $rows = null;

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
    ) {}

    public function canMultiselect(): bool
    {
        return !$this->getBrowse()->is_grid_view()
            && $this->getRows() !== []
            && ($this->canRemove() || $this->mayAdd() || $this->isDirectPlay());
    }

    /**
     * A collaborator curates the contents, the same rule the API and the playlist item list use.
     */
    public function canRemove(): bool
    {
        $collection = $this->getCollection();

        return $collection instanceof Collection && $collection->has_collaborate();
    }

    public function getCollectionId(): int
    {
        $collection = $this->getCollection();

        return ($collection instanceof Collection) ? $collection->getId() : 0;
    }

    /**
     * @return list<array{class: string, label: string, header: bool}>
     */
    public function getColumns(): array
    {
        $columns = [];
        if ($this->canMultiselect()) {
            $columns[] = ['class' => 'cel_select essential persist', 'label' => '', 'header' => false];
        }

        $columns[] = ['class' => 'cel_play essential', 'label' => T_('Play'), 'header' => false];
        $columns[] = ['class' => 'cel_cover optional', 'label' => T_('Art'), 'header' => true];
        $columns[] = ['class' => 'cel_title essential persist', 'label' => T_('Title'), 'header' => true];
        $columns[] = ['class' => 'cel_type essential', 'label' => T_('Type'), 'header' => true];
        $columns[] = ['class' => 'cel_artist optional', 'label' => T_('Parent'), 'header' => true];
        $columns[] = ['class' => 'cel_add essential', 'label' => '', 'header' => false];
        $columns[] = ['class' => 'cel_time optional', 'label' => T_('Time'), 'header' => true];

        if ($this->showRatings()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'header' => true];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Action'), 'header' => true];

        if ($this->canRemove()) {
            $columns[] = ['class' => 'cel_drag essential', 'label' => '', 'header' => false];
        }

        return $columns;
    }

    /**
     * @return list<array{action: string, url: string, icon: string, text: string, confirm?: string}>
     */
    public function getMultiselectActions(): array
    {
        $actions = [];
        if ($this->isDirectPlay()) {
            $actions[] = ['action' => 'ajax', 'url' => Ajax::url('?page=stream&action=directplay&object_type={type}&object_id={ids}'), 'icon' => 'play_circle', 'text' => T_('Play')];
            if (Stream_Playlist::check_autoplay_next()) {
                $actions[] = ['action' => 'ajax', 'url' => Ajax::url('?page=stream&action=directplay&object_type={type}&object_id={ids}&playnext=true'), 'icon' => 'menu_open', 'text' => T_('Play next')];
            }

            if (Stream_Playlist::check_autoplay_append()) {
                $actions[] = ['action' => 'ajax', 'url' => Ajax::url('?page=stream&action=directplay&object_type={type}&object_id={ids}&append=true'), 'icon' => 'low_priority', 'text' => T_('Play last')];
            }
        }

        $actions[] = ['action' => 'ajax', 'url' => Ajax::url('?action=basket&type={type}&id={ids}'), 'icon' => 'new_window', 'text' => T_('Add to Temporary Playlist')];
        if ($this->mayAdd()) {
            $actions[] = ['action' => 'playlist', 'url' => '', 'icon' => 'playlist_add', 'text' => Ui::get_add_to_list_label(true)];
        }

        if ($this->canRemove()) {
            $actions[] = [
                'action' => 'ajax',
                // `{track_ids}` addresses the `collection_map` rows directly, so one request covers a mixed selection
                'url' => Ajax::url('?page=collection&action=delete_track&collection_id=' . $this->getCollectionId() . '&browse_id=' . $this->getBrowse()->getId() . '&track_id={track_ids}'),
                'icon' => 'playlist_remove',
                'text' => T_('Remove from collection'),
                'confirm' => T_('Remove {count} selected items from this collection?'),
            ];
        }

        return $actions;
    }

    /**
     * @return list<array{item: displayable_item&library_item, type: string, trackId: int, track: int}>
     */
    public function getRows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $rows = [];
        foreach ($this->getContext()->objectIds as $object) {
            if (!is_array($object) || !isset($object['object_type'], $object['object_id'])) {
                continue;
            }

            $type = (is_string($object['object_type']))
                ? LibraryItemEnum::tryFrom($object['object_type'])
                : $object['object_type'];
            if (!$type instanceof LibraryItemEnum) {
                continue;
            }

            $item = $this->libraryItemLoader->load($type, (int) $object['object_id']);
            if (!$item instanceof displayable_item || !$this->isVisible($item)) {
                continue;
            }

            $rows[] = [
                'item' => $item,
                'type' => $type->value,
                'trackId' => (int) ($object['track_id'] ?? 0),
                'track' => (int) ($object['track'] ?? 0),
            ];
        }

        return $this->rows = $rows;
    }

    public function isDirectPlay(): bool
    {
        return (bool) $this->configContainer->get('directplay');
    }

    public function mayAdd(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    /**
     * @param array{item: displayable_item&library_item, type: string, trackId: int, track: int} $row
     */
    public function renderRow(array $row): string
    {
        return (new CollectionItemRowView(
            $this->configContainer->getWebPath('/client'),
            $row['item'],
            $row['type'],
            $this->getCollectionId(),
            $row['trackId'],
            $row['track'],
            $this->getBrowse()->getId(),
            $this->showRatings(),
            $this->canMultiselect(),
            $this->showMultiselect(),
            $this->isDirectPlay(),
            Stream_Playlist::check_autoplay_next(),
            Stream_Playlist::check_autoplay_append(),
            $this->mayAdd(),
            (bool) $this->configContainer->get('download'),
            $this->mayAdd() && (bool) $this->configContainer->get('share'),
            $this->canRemove()
        ))->render();
    }

    public function showMultiselect(): bool
    {
        return $this->canMultiselect() && $this->getBrowse()->is_use_select();
    }

    public function showRatings(): bool
    {
        return User::is_registered() && (bool) $this->configContainer->get('ratings');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/collection_items.phtml');
    }

    private function getCollection(): ?Collection
    {
        $collection = $this->getSupplementalObject('collection');

        return ($collection instanceof Collection) ? $collection : null;
    }

    /**
     * A disabled media is hidden from anyone who cannot manage the catalog it came from.
     */
    private function isVisible(library_item $item): bool
    {
        if (!property_exists($item, 'enabled') || $item->enabled) {
            return true;
        }

        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
    }
}
