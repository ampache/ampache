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

namespace Ampache\Module\Api;

use Ampache\Repository\Model\Folder;
use PHPUnit\Framework\TestCase;

/**
 * A raw folder id must never reach `getAmpacheId()`/`getAmpacheObject()` unprefixed: the legacy numeric
 * scheme reads any bare int below 100000000 as a catalog id, so an un-prefixed `folder.id` would silently
 * resolve as the wrong (or no) catalog instead of erroring. The `fo-` prefix is what avoids that collision.
 *
 * Both Subsonic_Api and OpenSubsonic_Api implement this identically but independently (no shared base),
 * so this is exercised against both.
 */
class SubsonicFolderIdTest extends TestCase
{
    /**
     * A bare, unprefixed folder id must not collide with the legacy numeric catalog id range
     */
    public function testBareFolderIdIsNotMistakenForALegacyCatalogId(): void
    {
        self::assertSame('catalog', Subsonic_Api::getAmpacheType('42'));
    }

    public function testOpenSubsonicFolderIdRoundTrips(): void
    {
        self::assertSame('fo-42', OpenSubsonic_Api::getFolderSubId(42));
        self::assertSame(42, OpenSubsonic_Api::getAmpacheId('fo-42'));
        self::assertSame('folder', OpenSubsonic_Api::getAmpacheType('fo-42'));
        self::assertInstanceOf(Folder::class, OpenSubsonic_Api::getAmpacheObject('fo-42'));
    }

    /**
     * The virtual root folder (id -1) has to survive the same round trip as a real folder
     */
    public function testRootFolderIdRoundTrips(): void
    {
        self::assertSame('fo--1', Subsonic_Api::getFolderSubId(-1));
        self::assertSame(-1, Subsonic_Api::getAmpacheId('fo--1'));

        $object = Subsonic_Api::getAmpacheObject('fo--1');
        self::assertInstanceOf(Folder::class, $object);
        self::assertSame(-1, $object->getId());
    }

    public function testSubsonicFolderIdRoundTrips(): void
    {
        self::assertSame('fo-42', Subsonic_Api::getFolderSubId(42));
        self::assertSame(42, Subsonic_Api::getAmpacheId('fo-42'));
        self::assertSame('folder', Subsonic_Api::getAmpacheType('fo-42'));
        self::assertInstanceOf(Folder::class, Subsonic_Api::getAmpacheObject('fo-42'));
    }
}
