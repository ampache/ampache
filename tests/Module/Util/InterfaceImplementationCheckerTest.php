<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Util;

use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\playable_item;
use Ampache\Repository\Model\Song_Preview;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use stdClass;

class InterfaceImplementationCheckerTest extends MockeryTestCase
{
    public function testIsPlayableItemReturnsTrueIfImplemented(): void
    {
        $instance = Mockery::mock(Song_Preview::class, playable_item::class);

        static::assertTrue(
            InterfaceImplementationChecker::is_playable_item(get_class($instance))
        );
    }

    public function testIsPlayableItemReturnsFalseIfNotImplemented(): void
    {
        $instance = new stdClass();

        static::assertFalse(
            InterfaceImplementationChecker::is_playable_item(get_class($instance))
        );
    }

    public function testIsLibraryItemReturnsFalseIfNotImplemented(): void
    {
        $instance = new stdClass();

        static::assertFalse(
            InterfaceImplementationChecker::is_library_item(get_class($instance))
        );
    }

    public function testIsMediaReturnsFalseIfNotImplemented(): void
    {
        $instance = new stdClass();

        static::assertFalse(
            InterfaceImplementationChecker::is_media(get_class($instance))
        );
    }
}
