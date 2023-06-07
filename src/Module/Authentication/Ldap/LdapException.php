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

declare(strict_types=0);

namespace Ampache\Module\Authentication\Ldap;

use Exception;

/**
 * This class defines custom LDAP exceptions that will be used in the
 * main LDAP class.
 */
class LdapException extends Exception
{
    /**
     * A LDAPException may be constructed thanks to a message, or an error
     * code. If the given argument is an integer, the exception will be
     * produced with message:
     *
     *     LDAP error: [errno] errmsg
     *
     * Otherwise, the provided message will be used.
     *
     * @param mixed $message
     */
    public function __construct($message)
    {
        if (is_int($message)) {
            $message = 'LDAP error: [' . $message . '] ' . ldap_err2str($message);
        }

        debug_event(__CLASS__, 'Exception: ' . $message, 3);
        parent::__construct($message);
    }
}
