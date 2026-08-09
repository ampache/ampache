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

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Override;

/**
 * A playlist's own page: the info box, its actions, and the browse of its contents.
 *
 * Its share link was the one action not wrapped in an `<li>`, inside a list of them.
 */
final class PlaylistPageView extends AbstractView
{
    /**
     * @param array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}> $objectIds
     */
    public function __construct(
        private readonly Playlist $playlist,
        private readonly array $objectIds,
        private readonly ZipHandlerInterface $zipHandler,
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly string $webPath,
    ) {}

    public function createBrowse(): Browse
    {
        return $this->browseFactory->create();
    }

    public function getArt(): string
    {
        ob_start();
        $this->playlist->display_art($this->getArtSize(), false, false);

        return (string) ob_get_clean();
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array
    {
        return (Ui::is_grid_view('playlist'))
            ? ['width' => 150, 'height' => 150]
            : ['width' => 384, 'height' => 384];
    }

    public function getDownloadName(): string
    {
        return rawurlencode($this->playlist->name ?? 'ampache_playlist');
    }

    public function getFullname(): string
    {
        ob_start();
        echo $this->playlist->getFullname();

        return (string) ob_get_clean();
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}>
     */
    public function getObjectIds(): array
    {
        return $this->objectIds;
    }

    public function getOwnerId(): int
    {
        return (int) $this->playlist->user;
    }

    public function getPlaylist(): Playlist
    {
        return $this->playlist;
    }

    public function getPlaylistId(): int
    {
        return $this->playlist->getId();
    }

    /**
     * A playlist built from a smartlist can be refreshed from it; 0 means it was not.
     */
    public function getSearchId(): int
    {
        return $this->playlist->has_search((int) $this->playlist->user);
    }

    public function getWebPath('/client'): string
    {
        return $this->webPath;
    }

    public function isAutoplayAppend(): bool
    {
        return Stream_Playlist::check_autoplay_append();
    }

    public function isAutoplayNext(): bool
    {
        return Stream_Playlist::check_autoplay_next();
    }

    public function isDirectPlay(): bool
    {
        return (bool) AmpConfig::get('directplay');
    }

    public function mayBatchDownload(): bool
    {
        return Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('playlist');
    }

    public function mayEdit(): bool
    {
        return $this->playlist->has_access();
    }

    /**
     * Reordering, sorting and de-duplicating all rewrite the track list, so they need write access.
     */
    public function mayReorder(): bool
    {
        $user = Core::get_global('user');

        return $user instanceof User
            && ($user->has_access(AccessLevelEnum::CONTENT_MANAGER) || $this->getOwnerId() === $user->getId());
    }

    public function showRatings(): bool
    {
        return User::is_registered() && (bool) AmpConfig::get('ratings');
    }

    public function showShare(): bool
    {
        return (bool) AmpConfig::get('share');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playlist/playlist.phtml');
    }
}
