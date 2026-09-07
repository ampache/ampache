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

namespace Ampache\Module\Authentication\Ldap;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

/**
 * The username reaching the LDAP search filter comes from the login form, so it must be escaped before it is
 * placed into the filter or a value like `*)(uid=*` would rewrite the query.
 */
#[RequiresPhpExtension('ldap')]
class LdapTest extends TestCase
{
    public function testBuildSearchEscapesABackslash(): void
    {
        $search = Ldap::build_search('(uid=%v)', 'inetOrgPerson', 'a\\b');

        self::assertSame('(&(objectclass=inetOrgPerson)(uid=a\5cb))', $search);
    }

    public function testBuildSearchEscapesFilterMetacharactersInTheLegacyForm(): void
    {
        $search = Ldap::build_search('uid', 'inetOrgPerson', '*)(uid=*');

        self::assertSame('(&(objectclass=inetOrgPerson)(uid=\2a\29\28uid=\2a))', $search);
    }

    public function testBuildSearchEscapesFilterMetacharactersInThePlaceholderForm(): void
    {
        $search = Ldap::build_search('(uid=%v)', 'inetOrgPerson', '*)(uid=*');

        // the injected parens/star must be escaped, leaving a single well-formed filter
        self::assertSame('(&(objectclass=inetOrgPerson)(uid=\2a\29\28uid=\2a))', $search);
    }

    public function testBuildSearchLeavesAPlainUsernameReadable(): void
    {
        self::assertSame(
            '(&(objectclass=inetOrgPerson)(uid=alice))',
            Ldap::build_search('(uid=%v)', 'inetOrgPerson', 'alice')
        );
    }
}
