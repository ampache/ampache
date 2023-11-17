<?php

/**
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

declare(strict_types=1);

namespace Ampache\Module\User;

final class PasswordGenerator implements PasswordGeneratorInterface
{
    public const DEFAULT_LENGTH = 8;

    /**
     * This generates a random password of the specified length
     * or will use a random length between 14-20
     */
    public function generate(?int $length = null): string
    {
        // set a random password length so it's not as easy to guess
        if ($length === null) {
            $length = rand(14, 20);
        }
        $strong   = true;
        $string   = openssl_random_pseudo_bytes((int) ceil($length * 0.67), $strong);
        $encode   = str_replace('=', '', base64_encode($string));

        return strtr($encode, '+/', '^*');
    }

    /**
     * Generate a simple token for file shares (a-z, A-Z & 0-9 only)
     */
    public function generate_token(): string
    {
        $random_bytes  = openssl_random_pseudo_bytes(self::DEFAULT_LENGTH);
        $random_string = base64_encode($random_bytes);
        $random_string = preg_replace("/[^a-zA-Z0-9]/", "", $random_string);
        $random_string = substr($random_string, 0, self::DEFAULT_LENGTH);

        return strtr($random_string, '+/', '^*');
    }
}
