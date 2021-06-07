<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Ajax\Handler\Defaults;

use Ampache\MockeryTestCase;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RefreshRightbarActionTest extends MockeryTestCase
{
    private MockInterface $ui;

    private RefreshRightbarAction $subject;

    public function setUp(): void
    {
        $this->ui = $this->mock(UiInterface::class);

        $this->subject = new RefreshRightbarAction(
            $this->ui
        );
    }

    public function testHandleReturnsOutput(): void
    {
        $request  = $this->mock(ServerRequestInterface::class);
        $response = $this->mock(ResponseInterface::class);
        $user     = $this->mock(User::class);

        $content = 'some-content';

        $this->ui->shouldReceive('ajaxInclude')
            ->with('rightbar.inc.php')
            ->once()
            ->andReturn($content);

        $this->assertSame(
            ['rightbar' => $content],
            $this->subject->handle($request, $response, $user)
        );
    }
}
