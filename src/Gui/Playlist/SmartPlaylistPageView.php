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
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Override;

/**
 * A smartlist's own page: its actions, its rule editor and the browse of what the rules match.
 *
 * Its form carried `enctype` twice, and every hidden field but the name went out unescaped.
 */
final class SmartPlaylistPageView extends AbstractView
{
    /**
     * The rule editor and the per-type options only exist for these; anything else is read-only here.
     */
    private const array OPTION_TYPES = ['album', 'artist', 'song'];

    private ?Browse $browse = null;

    /**
     * @param array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}> $objectIds
     */
    public function __construct(
        private readonly Search $playlist,
        private readonly array $objectIds,
        private readonly ZipHandlerInterface $zipHandler,
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly string $webPath,
        private readonly bool $mayUse,
        private readonly bool $mayBatchDownload,
    ) {}

    public function getAddToListLabel(): string
    {
        return Ui::get_add_to_list_label();
    }

    /**
     * The browse id goes into the form, so it has to exist before the form renders.
     */
    public function getBrowse(): Browse
    {
        if ($this->browse === null) {
            $browse = $this->browseFactory->create();
            $browse->set_type('playlist_media');
            $browse->set_use_filters(false);
            $browse->add_supplemental_object('playlist', $this->playlist);
            $browse->set_static_content(false);
            $this->browse = $browse;
        }

        return $this->browse;
    }

    public function getFormAction(): string
    {
        return $this->webPath . '/smartplaylist.php?action=show&playlist_id=' . $this->getPlaylistId();
    }

    public function getFullname(): string
    {
        ob_start();
        echo $this->playlist->getFullname();

        return (string) ob_get_clean();
    }

    public function getLimit(): int
    {
        return $this->playlist->limit;
    }

    public function getName(): string
    {
        return (string) $this->playlist->name;
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}>
     */
    public function getObjectIds(): array
    {
        return $this->objectIds;
    }

    public function getObjectType(): string
    {
        return $this->playlist->objectType;
    }

    public function getOwnerId(): int
    {
        return (int) $this->playlist->user;
    }

    public function getPlaylist(): Search
    {
        return $this->playlist;
    }

    public function getPlaylistId(): int
    {
        return $this->playlist->getId();
    }

    public function getRandom(): int
    {
        return (int) $this->playlist->random;
    }

    public function getTotalDuration(): int
    {
        return Search::get_total_duration($this->objectIds);
    }

    public function getType(): string
    {
        return (string) $this->playlist->type;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function getZipHandler(): ZipHandlerInterface
    {
        return $this->zipHandler;
    }

    public function mayBatchDownload(): bool
    {
        return $this->mayBatchDownload && $this->zipHandler->isZipable('search');
    }

    public function mayEdit(): bool
    {
        return $this->playlist->has_access();
    }

    /**
     * Passed in rather than asked for: a view that calls `Access::check()` reaches the container, which a
     * unit test rendering it does not have.
     */
    public function mayUse(): bool
    {
        return $this->mayUse;
    }

    /**
     * Only a song smartlist can be saved back as a playlist, which is what the three buttons do.
     */
    public function showEditButtons(): bool
    {
        return $this->getObjectType() === 'song' && $this->mayUse();
    }

    public function showRatings(): bool
    {
        return User::is_registered() && (bool) AmpConfig::get('ratings');
    }

    public function showSearchOptions(): bool
    {
        return in_array($this->getObjectType(), self::OPTION_TYPES, true);
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playlist/smartplaylist.phtml');
    }
}
