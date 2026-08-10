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

namespace Ampache\Gui\Playlist;

use Ampache\Gui\View\TemplateInterface;

interface NewPlaylistDialogAdapterInterface extends TemplateInterface
{
    public function getAjaxUri(): string;

    public function getCollectionHeading(): string;

    /**
     * The collections this user may curate that will accept what is being added
     *
     * @return list<\Ampache\Repository\Model\Collection>
     */
    public function getCollections(): array;

    /**
     * Whether the collection half of the dialog is offered at all
     */
    public function getCollectionsEnabled(): bool;

    public function getNewCollectionTitle(): string;

    public function getNewPlaylistTitle(): string;

    public function getObjectGroups(): string;

    public function getObjectIds(): string;

    public function getObjectType(): string;

    public function getPlaylistHeading(): string;

    public function getPlaylists(): array;

    /**
     * Whether the playlist half is offered; a genre, for instance, only belongs in a collection
     */
    public function getPlaylistsEnabled(): bool;
}
