<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Repository\Model\Playlist;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;

final readonly class NewPlaylistDialogAdapter implements NewPlaylistDialogAdapterInterface
{
    public function __construct(
        private PlaylistLoaderInterface $playlistLoader,
        private AjaxUriRetrieverInterface $ajaxUriRetriever,
        private GuiGatekeeperInterface $gatekeeper,
        private string $object_type,
        private string $object_ids
    ) {
    }

    /**
     * Returns a list containing all playlists of the current user
     *
     * @return Playlist[]
     */
    public function getPlaylists(): array
    {
        return $this->playlistLoader->loadByUserId(
            $this->gatekeeper->getUserId()
        );
    }

    /**
     * Returns the ajax api base uri
     */
    public function getAjaxUri(): string
    {
        return $this->ajaxUriRetriever->getAjaxUri();
    }

    public function getObjectType(): string
    {
        return $this->object_type;
    }

    public function getObjectIds(): string
    {
        return $this->object_ids;
    }

    public function getNewPlaylistTitle(): string
    {
        return T_('Playlist Name');
    }
}
