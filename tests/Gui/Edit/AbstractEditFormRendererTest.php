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

namespace Ampache\Gui\Edit;

use Ampache\Gui\Edit\Renderer\AlbumEditFormRenderer;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\User;
use Psr\Container\ContainerInterface;

/**
 * `mayDisable()` is what puts the State menu in the edit dialog, so it is the only thing standing between a
 * listener and the field that takes a release off the shelves.
 */
class AbstractEditFormRendererTest extends MockeryTestCase
{
    public function testMayDisableIsGrantedToAManager(): void
    {
        $this->bootUser(42, true);

        self::assertTrue((new AlbumEditFormRenderer())->mayDisable());
    }

    public function testMayDisableIsRefusedBelowManager(): void
    {
        $this->bootUser(42, false);

        self::assertFalse((new AlbumEditFormRenderer())->mayDisable());
    }

    /**
     * A page rendered with nobody signed in has no level to read, and asking for one would resolve to whatever
     * the checker makes of a missing id rather than to a refusal
     */
    public function testMayDisableIsRefusedWithNoUserAtAll(): void
    {
        $privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        // the tell that the user is checked first: the level is never even asked for
        $privilegeChecker->shouldNotReceive('check');

        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')->with(PrivilegeCheckerInterface::class)->andReturn($privilegeChecker);

        $GLOBALS['dic'] = $dic;
        unset($GLOBALS['user']);

        self::assertFalse((new AlbumEditFormRenderer())->mayDisable());
    }

    private function bootUser(int $userId, bool $isManager): void
    {
        $user     = $this->mock(User::class);
        $user->id = $userId;
        $user->shouldReceive('getId')->andReturn($userId);

        $privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $userId)
            ->andReturn($isManager);

        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')->with(PrivilegeCheckerInterface::class)->andReturn($privilegeChecker);

        $GLOBALS['dic']  = $dic;
        $GLOBALS['user'] = $user;
    }
}
