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

namespace Ampache\Module\Catalog;

/**
 * The catalog backends, which are also the suffix of their own settings table (`catalog_local`, ...)
 *
 * The value reaches a statement as a table name, so it may not be a plain string; the cases mirror the
 * keys of `Catalog::CATALOG_TYPES` and the `$type` property each subclass returns from `get_type()`.
 *
 * @see Catalog::CATALOG_TYPES
 */
enum CatalogTypeEnum: string
{
    case BEETS       = 'beets';
    case BEETSREMOTE = 'beetsremote';
    case DROPBOX     = 'dropbox';
    case LOCAL       = 'local';
    case REMOTE      = 'remote';
    case SEAFILE     = 'seafile';
    case SUBSONIC    = 'subsonic';

    /**
     * The settings table this backend keeps its own columns in
     */
    public function tableName(): string
    {
        return 'catalog_' . $this->value;
    }
}
