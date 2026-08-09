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
use Ampache\Module\Catalog\Catalog;
use Override;

/**
 * The per-catalog settings form.
 */
final class EditCatalogView extends AbstractView
{
    public function __construct(
        private readonly string $adminPath,
        private readonly Catalog $catalog,
    ) {}

    public function getActionUrl(): string
    {
        return $this->adminPath . '/catalog.php';
    }

    public function getCatalog(): Catalog
    {
        return $this->catalog;
    }

    /**
     * The pattern legend, as specifier => what it inserts.
     *
     * @return array<string, string>
     */
    public function getFormatSpecifiers(): array
    {
        return [
            '%A' => T_('Album'),
            '%B' => T_('Album Artist'),
            '%a' => T_('Song Artist'),
            '%m' => T_('Artist'),
            '%t' => T_('Song Title'),
            '%T' => T_('Track (0 padded)'),
            '%d' => T_('Disk'),
            '%g' => T_('Genre'),
            '%y' => T_('Year'),
            '%Y' => T_('Original Year'),
            '%c' => T_('Comment'),
            '%l' => T_('Label'),
            '%r' => T_('Release Type'),
            '%R' => T_('Release Status'),
            '%s' => T_('Release Comment'),
            '%C' => T_('Catalog Number'),
            '%b' => T_('Barcode'),
            '%o' => T_('Ignore'),
        ];
    }

    /**
     * The catalog info is markup, so the name is escaped separately rather than the whole title.
     */
    public function getTitle(): string
    {
        /* HINT: Catalog Name */
        return sprintf(
            T_('Settings for Catalog: %s'),
            $this->e($this->catalog->name) . ' (' . $this->catalog->get_f_info() . ')'
        );
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit_catalog.phtml');
    }
}
