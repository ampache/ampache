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

namespace Ampache\Gui\Admin;

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Song;
use Iterator;
use Override;

/**
 * The re-enable form listing every song a catalog verify disabled.
 */
final class DisabledSongsView extends AbstractView
{
    /**
     * @param Iterator<Song> $songs
     */
    public function __construct(
        private readonly string $adminPath,
        private readonly Iterator $songs,
    ) {}

    public function getActionUrl(): string
    {
        return $this->adminPath . '/catalog.php';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [T_('Select'), T_('Title'), T_('Album'), T_('Artist'), T_('Filename'), T_('Addition Time')];
    }

    /**
     * @return Iterator<Song>
     */
    public function getSongs(): Iterator
    {
        return $this->songs;
    }

    /**
     * valid() primes the generator, and on an empty result that also closes it, so this must be asked
     * before anything iterates.
     */
    public function hasSongs(): bool
    {
        return $this->songs->valid();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('disabled_songs.phtml');
    }
}
