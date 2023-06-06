<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Application\Admin\Access\Lib;

interface AccessListItemInterface
{
    /**
     * take the int level and return a named level
     */
    public function getLevelName(): string;

    /**
     * Return a name for the users covered by this ACL.
     */
    public function getUserName(): string;

    /**
     * This function returns the pretty name for our current type.
     */
    public function getTypeName(): string;

    /**
     * Returns a human readable representation of the start ip
     */
    public function getStartIp(): string;

    /**
     * Returns a human readable representation of the end ip
     */
    public function getEndIp(): string;

    /**
     * Return the acl item name
     */
    public function getName(): string;

    /**
     * Return the acl item id
     */
    public function getId(): int;

    /**
     * Returns the acl item level
     */
    public function getLevel(): int;

    /**
     * Returns the acl item type
     */
    public function getType(): string;

    /**
     * Returns the acl item user id
     */
    public function getUserId(): int;
}
