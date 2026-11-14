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

namespace Ampache\Module\Application\Installation;

use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\InstallationHelperInterface;
use Ampache\Module\Util\EnvironmentInterface;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class DefaultActionTest extends TestCase
{
    /** A password with an ampersand locked the installer out of its own database (#2490) */
    #[RunInSeparateProcess]
    public function testCreateDbCreatesTheUserWithTheUnscrubbedPassword(): void
    {
        $password = 'p&ss\w<o>rd';

        $_POST = [
            'action' => 'create_db',
            'htmllang' => 'en_US',
            'db_user' => 'create_db_user',
            'db_username' => 'ampache',
            'db_password' => $password,
        ];
        $_REQUEST = $_POST;

        $installationHelper = $this->createMock(InstallationHelperInterface::class);
        $installationHelper->method('install_check_status')
            ->willReturn(true);
        $installationHelper->expects(self::once())
            ->method('install_insert_db')
            ->with('ampache', $password)
            ->willThrowException(new RuntimeException('database user created'));

        $subject = new DefaultAction($installationHelper, $this->createStub(EnvironmentInterface::class));

        $this->expectExceptionMessage('database user created');

        try {
            $subject->run($this->createStub(ServerRequestInterface::class), $this->createStub(GuiGatekeeperInterface::class));
        } finally {
            restore_error_handler();
        }
    }
}
