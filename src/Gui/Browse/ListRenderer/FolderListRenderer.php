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
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Override;

/**
 * The folder browse, which also lists the songs, videos and episodes a folder holds.
 *
 * Its empty state declared nine columns where the header emits at most eight.
 */
final class FolderListRenderer extends AbstractBrowseListRenderer
{
    /**
     * A collection pinned to folders hands over bare ids, having no types to send with them.
     */
    private const string BARE_ID_TYPE = 'folder';

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly GatekeeperFactoryInterface $gatekeeperFactory,
        private readonly GuiFactoryInterface $guiFactory,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
    ) {}

    /**
     * @return list<array{class: string, label: string, sort: null|string}>
     */
    public function getColumns(): array
    {
        $columns = [
            ['class' => 'cel_play essential', 'label' => '', 'sort' => null],
            ['class' => $this->getCoverClass() . ' optional', 'label' => T_('Art'), 'sort' => null],
            ['class' => $this->getFolderClass() . ' essential persist', 'label' => T_('Name'), 'sort' => 'name'],
            ['class' => 'cel_add essential', 'label' => '', 'sort' => null],
            ['class' => 'cel_songs optional', 'label' => T_('# Items'), 'sort' => null],
        ];

        if ($this->showPlayedTimes()) {
            $columns[] = ['class' => $this->getCounterClass() . ' optional', 'label' => T_('Played'), 'sort' => null];
        }

        if ($this->showRatings()) {
            $columns[] = ['class' => 'cel_ratings optional', 'label' => T_('Rating'), 'sort' => 'rating'];
        }

        $columns[] = ['class' => 'cel_action essential', 'label' => T_('Actions'), 'sort' => null];

        return $columns;
    }

    public function getCounterClass(): string
    {
        return 'cel_counter';
    }

    public function getCoverClass(): string
    {
        return 'cel_cover';
    }

    public function getFolderClass(): string
    {
        return 'cel_folder';
    }

    /**
     * A browse of a collection's folders has no folder being walked into, so it gets an empty one.
     */
    public function getParentFolder(): Folder
    {
        $folder = $this->getSupplementalObject('folder');

        return ($folder instanceof Folder) ? $folder : new Folder(-1);
    }

    /**
     * @return list<array{type: string, item: Folder|Podcast_Episode|Song|Video}>
     */
    public function getRows(): array
    {
        $rows = [];
        foreach ($this->getContext()->objectIds as $object) {
            [$type, $objectId] = $this->parse((string) $object);
            $enum              = LibraryItemEnum::tryFrom($type);
            if ($enum === null) {
                continue;
            }

            $item = $this->libraryItemLoader->load($enum, $objectId, [Folder::class, Podcast_Episode::class, Song::class, Video::class]);
            if (!$item instanceof Folder && !$item instanceof Podcast_Episode && !$item instanceof Song && !$item instanceof Video) {
                continue;
            }

            $rows[] = ['type' => $type, 'item' => $item];
        }

        return $rows;
    }

    public function renderRow(Folder|Podcast_Episode|Song|Video $item, string $type): string
    {
        return $this->guiFactory->createFolderRowView(
            $this->gatekeeperFactory->createGuiGatekeeper(),
            $this->getParentFolder(),
            $item,
            $type,
            $this->showRatings(),
            $this->showPlayedTimes(),
            $this->showTemporaryAdd($item),
            $this->mayUse(),
            $this->getCoverClass(),
            $this->getFolderClass(),
            $this->getCounterClass()
        )->render();
    }

    public function showPlayedTimes(): bool
    {
        return (bool) $this->configContainer->get('show_played_times');
    }

    public function showRatings(): bool
    {
        return User::is_registered() && (bool) $this->configContainer->get('ratings');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/folders.phtml');
    }

    private function mayUse(): bool
    {
        return $this->gatekeeperFactory->createGuiGatekeeper()
            ->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function parse(string $object): array
    {
        if (preg_match('/^[0-9]+$/', $object) === 1) {
            return [self::BARE_ID_TYPE, (int) $object];
        }

        preg_match('/([a-z_]+)-([0-9]+)/', $object, $matches);

        return [$matches[1] ?? '', (int) ($matches[2] ?? 0)];
    }

    /**
     * The queue holds the items themselves, so a folder with nothing playable has nothing to offer it.
     */
    private function showTemporaryAdd(Folder|Podcast_Episode|Song|Video $item): bool
    {
        if (!$this->mayUse()) {
            return false;
        }

        $limit = $this->configContainer->getInt('direct_play_limit');
        if (!$item instanceof Folder || $limit <= 0 || $this->getBrowse()->is_grid_view()) {
            return true;
        }

        return $item->playable && $item->object_count > 0 && $item->object_count <= $limit;
    }
}
