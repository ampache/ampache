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

namespace Ampache\Gui\Edit\Renderer;

use Ampache\Gui\Edit\AbstractEditFormRenderer;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Override;

/**
 * The playlist and smartlist edit dialogs, which differ only by the smartlist's random and limit rules.
 *
 * `playlist` is a strict column subset of `search`, so one form covers both. It reached its selected type
 * through a variable-variable pair of locals before.
 */
final class PlaylistEditFormRenderer extends AbstractEditFormRenderer
{
    /**
     * @return list<int>
     */
    public function getCollaborateIds(): array
    {
        $ids = [];
        foreach (explode(',', (string) $this->getItem()->collaborate) as $id) {
            if ($id !== '') {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * The collaborate list is every valid user; the owner list is the one the dialog was handed.
     *
     * @return array<int, string>
     */
    public function getCollaborators(): array
    {
        return User::getValidArray();
    }

    public function getLimit(): int
    {
        $item = $this->getItem();

        return ($item instanceof Search) ? $item->limit : 0;
    }

    public function getName(): string
    {
        return (string) $this->getItem()->name;
    }

    public function getOwnerId(): int
    {
        return (int) $this->getItem()->user;
    }

    public function getPlaylistId(): int
    {
        return $this->getItem()->getId();
    }

    public function getType(): string
    {
        return (string) $this->getItem()->type;
    }

    /**
     * @return array<int, string>
     */
    public function getUsers(): array
    {
        return $this->getContext()->users;
    }

    public function isRandom(): bool
    {
        $item = $this->getItem();

        return $item instanceof Search && (bool) $item->random;
    }

    /**
     * Only a smartlist is rule driven, so only it offers the random and limit controls.
     */
    public function isSmartlist(): bool
    {
        return $this->getItem() instanceof Search;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit/playlist.phtml');
    }

    private function getItem(): Playlist|Search
    {
        /** @var Playlist|Search $item */
        $item = $this->getContext()->item;

        return $item;
    }
}
