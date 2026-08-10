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

namespace Ampache\Gui\Catalog;

/**
 * The catalog processes that report progress into a live page.
 *
 * Each case owns the element ids the process' ajax updates write into, so the javascript and the markup
 * cannot drift apart.
 */
enum CatalogProgressTypeEnum: string
{
    case ADD    = 'add';
    case ART    = 'art';
    case CLEAN  = 'clean';
    case VERIFY = 'verify';

    public function getBoxClass(): string
    {
        return match ($this) {
            self::ADD => 'box box_adds_catalog',
            self::ART => 'box box_gather_art',
            self::CLEAN => 'box box_clean_catalog',
            self::VERIFY => 'box box_verify_catalog',
        };
    }

    public function getCounterElementId(): string
    {
        return match ($this) {
            self::ADD => 'add_count_',
            self::ART => 'count_art_',
            self::CLEAN => 'clean_count_',
            self::VERIFY => 'verify_count_',
        };
    }

    public function getReaderElementId(): string
    {
        return match ($this) {
            self::ADD => 'add_dir_',
            self::ART => 'read_art_',
            self::CLEAN => 'clean_dir_',
            self::VERIFY => 'verify_dir_',
        };
    }
}
