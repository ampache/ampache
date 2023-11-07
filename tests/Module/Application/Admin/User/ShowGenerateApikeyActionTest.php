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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ShowGenerateApikeyActionTest extends TestCase
{
    use UserAdminConfirmationTestTrait;

    private MockObject&UiInterface $ui;

    private ShowGenerateApikeyAction $subject;

    public function setUp(): void
    {
        $this->ui = $this->createMock(UiInterface::class);

        $this->subject = new ShowGenerateApikeyAction(
            $this->ui,
        );
    }

    public function testHandleRendersConfirmation(): void
    {
        $this->createConfirmationExpectations(
            function (int $userId): void {
                $this->ui->expects(static::once())
                    ->method('showConfirmation')
                    ->with(
                        'Are You Sure?',
                        'This will replace your existing API key',
                        sprintf(
                            'admin/users.php?action=%s&user_id=%d',
                            GenerateApikeyAction::REQUEST_KEY,
                            $userId
                        ),
                        1,
                        'generate_apikey'
                    );
            }
        );
    }
}
