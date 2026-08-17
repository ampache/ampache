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
use Ampache\Gui\Collection\CollectionRowView;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\User;
use Override;

/**
 * The collection browse.
 */
final class CollectionListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly CollectionRepositoryInterface $collectionRepository,
    ) {}

    /**
     * @return list<Collection>
     */
    public function getCollections(): array
    {
        $user        = Core::get_global('user');
        $viewer      = ($user instanceof User) ? $user : null;
        $collections = [];
        foreach ($this->getObjectIds() as $objectId) {
            $collection = $this->collectionRepository->findById($objectId);
            if ($collection === null || !$collection->isVisible($viewer)) {
                continue;
            }

            $collections[] = $collection;
        }

        return $collections;
    }

    /**
     * @return list<array{class: string, label: string, sort: null|string, header: bool}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_play essential', 'label' => '', 'sort' => null, 'header' => false],
            ['class' => $this->getCoverClass() . ' optional', 'label' => T_('Art'), 'sort' => null, 'header' => true],
            ['class' => 'cel_collection essential persist', 'label' => T_('Name'), 'sort' => 'name', 'header' => true],
            ['class' => 'cel_medias optional', 'label' => T_('# Items'), 'sort' => 'last_count', 'header' => true],
            ['class' => 'cel_type optional', 'label' => T_('Type'), 'sort' => 'type', 'header' => true],
            ['class' => 'cel_object_type optional', 'label' => T_('Holds'), 'sort' => 'object_type', 'header' => true],
        ];

        if ($this->showRatings()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'sort' => 'rating', 'header' => true];
        }

        $columns[] = ['class' => 'cel_owner essential', 'label' => T_('Owner'), 'sort' => 'username', 'header' => true];
        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Actions'), 'sort' => null, 'header' => true];

        return $columns;
    }

    public function getCoverClass(): string
    {
        return $this->getCellClass('cel_cover', 'grid_cover');
    }

    public function getCreateUrl(): string
    {
        return $this->configContainer->getWebPath('/client') . '/collection.php?action=show_create';
    }

    public function mayCreate(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    public function renderRow(Collection $collection): string
    {
        return new CollectionRowView(
            $this->configContainer->getWebPath('/client'),
            $collection,
            $this->getCoverClass(),
            (bool) $this->configContainer->get('directplay'),
            $this->showRatings()
        )->render();
    }

    public function showRatings(): bool
    {
        return User::is_registered() && (bool) $this->configContainer->get('ratings');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/collections.phtml');
    }
}
