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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Method\AbstractCatalogActionMethod;

/**
 * Kicks off a catalog update or clean for the selected catalog
 *
 * CHANGED_IN_API_VERSION=800000
 *
 * Api version 8 reports the catalog id as `filter` and accepts `catalog` as an alias. It also adds
 * the `update_catalog` task, which runs clean, verify then add in that order.
 */
final class CatalogAction8Method extends AbstractCatalogActionMethod
{
    protected const string FILTER_ALIAS = 'catalog';

    protected const string FILTER_KEY = 'filter';

    /** @var string[] */
    protected const array TASKS = [
        'add_to_catalog',
        'clean_catalog',
        'verify_catalog',
        'update_catalog',
        'gather_art',
        'garbage_collect',
    ];
}
