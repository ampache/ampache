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

namespace Ampache\Gui\Search;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\ZipHandlerInterface;
use Override;

/**
 * The action box beside a set of search results.
 */
final class SearchOptionsView extends AbstractView
{
    /**
     * Only these can be queued or played; anything else the search returns is not streamable.
     */
    private const array PLAYABLE_TYPES = ['song', 'album', 'artist'];

    public function __construct(
        private readonly Browse $browse,
        private readonly string $searchType,
        private readonly ZipHandlerInterface $zipHandler,
        private readonly string $webPath,
        private readonly bool $mayBatchDownload,
    ) {}

    public function getBrowseId(): int
    {
        return (int) $this->browse->id;
    }

    public function getSearchType(): string
    {
        return $this->searchType;
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

    public function isPlayable(): bool
    {
        return in_array($this->searchType, self::PLAYABLE_TYPES, true);
    }

    public function mayBatchDownload(): bool
    {
        return $this->mayBatchDownload && $this->zipHandler->isZipable($this->searchType);
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('search/options.phtml');
    }
}
