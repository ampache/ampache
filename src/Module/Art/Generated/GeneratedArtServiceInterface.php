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

namespace Ampache\Module\Art\Generated;

use Ampache\Module\Art\Generated\Template\TemplateInterface;

interface GeneratedArtServiceInterface
{
    /**
     * @return list<TemplateInterface>
     */
    public function getTemplates(): array;

    /**
     * Both the instance switch and the listener's own preference have to be on.
     */
    public function isEnabled(): bool;

    /**
     * @param null|string $forceTemplate template id from the url, overriding the viewer's preference
     * @param bool $force draw even when the viewer has not turned this on for themselves
     *
     * @return array{svg: string, etag: string}|null null when the item is covered by nothing we draw
     */
    public function render(string $objectType, int $objectId, int $edge, ?string $forceTemplate = null, bool $force = false): ?array;

    /**
     * Draws an invented item, for the previews beside each template in the preferences page.
     *
     * @return array{svg: string, etag: string}|null null when the motif is not one we draw
     */
    public function renderPreview(string $motif, ?string $templateId, int $edge): ?array;

    public function resolveTemplate(): TemplateInterface;

    /**
     * Whether a drawn tile is shown even when the administrator has set a custom_blankalbum.
     */
    public function takesPrecedenceOverCustom(): bool;
}
