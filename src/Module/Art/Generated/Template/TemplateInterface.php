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

use Ampache\Module\Art\Generated\Recipe;

interface TemplateInterface
{
    /**
     * The value stored in the user preference.
     */
    public function getId(): string;

    /**
     * Shown in the preferences page next to its preview.
     */
    public function getLabel(): string;

    /**
     * Draws one tile as an SVG document.
     *
     * $edge is the size the caller asked for. Below roughly a hundred pixels the name cannot be read at
     * all, so a template is expected to drop it rather than draw an unreadable smear.
     */
    public function render(Recipe $recipe, int $edge): string;
}
