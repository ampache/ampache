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

namespace Ampache\Repository\Model;

use Ampache\Repository\UserRepositoryInterface;

/**
 * The `user` columns a single-field write is allowed to target.
 *
 * The column name is interpolated into the statement, so this is what stops it being caller-supplied. The three
 * token columns are here because clearing one is the same write as setting it, only with a null value.
 *
 * @see UserRepositoryInterface::setField()
 */
enum UserFieldEnum: string
{
    case ACCESS               = 'access';
    case APIKEY               = 'apikey';
    case CATALOG_FILTER_GROUP = 'catalog_filter_group';
    case CITY                 = 'city';
    case EMAIL                = 'email';
    case FULLNAME             = 'fullname';
    case FULLNAME_PUBLIC      = 'fullname_public';
    case PASSWORD             = 'password';
    case RSSTOKEN             = 'rsstoken';
    case STATE                = 'state';
    case STREAMTOKEN          = 'streamtoken';
    case SUBSONIC_SECRET      = 'subsonic_secret';
    case USERNAME             = 'username';
    case WEBSITE              = 'website';
}
