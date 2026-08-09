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

use Ampache\Repository\Model\Wanted;
use Ampache\Repository\WantedRepositoryInterface;
use Override;

/**
 * The wanted-album browse.
 */
final class WantedListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly WantedRepositoryInterface $wantedRepository,
    ) {}

    /**
     * @return list<array{class: string, label: string, sort: null|string}>
     */
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_album essential', 'label' => T_('Album'), 'sort' => 'name'],
            ['class' => 'cel_artist essential', 'label' => T_('Artist'), 'sort' => 'artist'],
            ['class' => 'cel_year optional', 'label' => T_('Year'), 'sort' => 'year'],
            ['class' => 'cel_user optional', 'label' => T_('User'), 'sort' => 'user'],
            ['class' => 'cel_action essential', 'label' => T_('Actions'), 'sort' => null],
        ];
    }

    /**
     * @return list<Wanted>
     */
    public function getWantedAlbums(): array
    {
        $albums = [];
        foreach ($this->getObjectIds() as $objectId) {
            $album = $this->wantedRepository->findById($objectId);
            if ($album !== null) {
                $albums[] = $album;
            }
        }

        return $albums;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/wanted_albums.phtml');
    }
}
