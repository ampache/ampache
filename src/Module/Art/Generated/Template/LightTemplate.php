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
 * The illustrated template, drawn for a light interface.
 *
 * Same motifs and the same accent as the dark one, on a pale ground: a tile built for a dark theme
 * glares on a light page, and simply inverting it would wreck the contrast of the accent.
 */
final class LightTemplate extends AbstractSvgTemplate
{
    public function getId(): string
    {
        return 'light';
    }

    public function getLabel(): string
    {
        return T_('Light');
    }

    protected function isDark(): bool
    {
        return false;
    }
}
