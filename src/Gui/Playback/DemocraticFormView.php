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

namespace Ampache\Gui\Playback;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Playback\Democratic;
use Override;

/**
 * The democratic playlist configuration form.
 *
 * Its level row never closed its `<tr>`.
 */
final class DemocraticFormView extends AbstractView
{
    public function __construct(
        private readonly Democratic $democratic,
        private readonly string $webPath,
    ) {}

    public function getBasePlaylistSelect(): string
    {
        return Democratic::show_playlist_select('democratic', (string) $this->democratic->base_playlist);
    }

    public function getCooldown(): string
    {
        return (string) $this->democratic->cooldown;
    }

    public function getFormAction(): string
    {
        return $this->webPath . '/democratic.php?action=create';
    }

    public function getLevel(): int
    {
        return (int) $this->democratic->level;
    }

    /**
     * @return array<int, string>
     */
    public function getLevels(): array
    {
        return [
            25 => T_('User'),
            50 => T_('Content Manager'),
            75 => T_('Catalog Manager'),
            100 => T_('Admin'),
        ];
    }

    public function getName(): string
    {
        return (string) $this->democratic->name;
    }

    public function isPrimary(): bool
    {
        return (bool) $this->democratic->primary;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('democratic_form.phtml');
    }
}
