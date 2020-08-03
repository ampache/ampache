<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

use Sabre\DAV;

/**
 * WebDAV Directory Class
 *
 * This class wrap Ampache albums and artist to WebDAV directories.
 *
 */
class WebDAV_Auth extends DAV\Auth\Backend\AbstractBasic
{
    /**
     * validateUserPass
     * @param $username
     * @param $password
     * @return mixed
     */
    protected function validateUserPass($username, $password)
    {
        $auth = Auth::login($username, $password, true);

        return $auth['success'];
    }
} // end webdav_auth.class
