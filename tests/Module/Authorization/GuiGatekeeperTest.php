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

namespace Ampache\Module\Authorization;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Mockery\MockInterface;

class GuiGatekeeperTest extends MockeryTestCase
{
    /** @var MockInterface|PrivilegeCheckerInterface|null */
    private MockInterface $privilegeChecker;

    private ?GuiGatekeeper $subject;

    protected function setUp(): void
    {
        $this->privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);

        $this->subject = new GuiGatekeeper(
            $this->privilegeChecker
        );
    }

    public function testMayAccessPerformsPrivilegeCheck(): void
    {
        $type  = 'some-type';
        $level = 666;

        $this->privilegeChecker->shouldReceive('check')
            ->with($type, $level)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->mayAccess($type, $level)
        );
    }
}
