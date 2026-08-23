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

namespace Ampache\Module\Art\Generated\Template;

/**
 * The illustrated template, drawn for a dark interface.
 *
 * Each motif says what the object is: an album is a record, a song is a waveform, a playlist is a list
 * of them. The drawing is decoration only in the sense that it is not the name; it still carries type.
 */
final class DarkTemplate extends AbstractSvgTemplate
{
    public function getId(): string
    {
        return 'dark';
    }

    public function getLabel(): string
    {
        return T_('Illustrated, dark');
    }

    protected function isDark(): bool
    {
        return true;
    }
}
