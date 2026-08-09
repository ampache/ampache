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

namespace Ampache\Gui\Democratic;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Playback\Democratic;
use Ampache\Repository\Model\Playlist;
use Override;

/**
 * The democratic playlists an admin can manage.
 */
final class ManageDemocraticView extends AbstractView
{
    /** @var list<array{democratic: Democratic, playlist: Playlist}>|null */
    private ?array $rows = null;

    /**
     * @param list<int> $democraticIds
     */
    public function __construct(
        private readonly string $webPath,
        private readonly array $democraticIds,
    ) {}

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [T_('Playlist'), T_('Base Playlist'), T_('Cooldown'), T_('Level'), T_('Default'), T_('Songs'), T_('Action')];
    }

    public function getCreateUrl(): string
    {
        return $this->webPath . '/democratic.php?action=show_create';
    }

    public function getDeleteUrl(Democratic $democratic): string
    {
        return $this->webPath . '/democratic.php?action=delete&democratic_id=' . $democratic->id;
    }

    /**
     * A democratic playlist whose base playlist has gone is skipped rather than half-rendered.
     *
     * @return list<array{democratic: Democratic, playlist: Playlist}>
     */
    public function getRows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $rows = [];
        foreach ($this->democraticIds as $democraticId) {
            $democratic = new Democratic($democraticId);
            $playlist   = new Playlist($democratic->base_playlist);
            if ($playlist->isNew()) {
                continue;
            }

            $rows[] = ['democratic' => $democratic, 'playlist' => $playlist];
        }

        return $this->rows = $rows;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('manage_democratic.phtml');
    }
}
