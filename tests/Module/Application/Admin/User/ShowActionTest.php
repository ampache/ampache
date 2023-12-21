<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\Admin\User;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowActionTest extends MockeryTestCase
{
    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private ?ShowAction $subject;

    protected function setUp(): void
    {
        $this->ui           = $this->mock(UiInterface::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new ShowAction(
            $this->ui,
            $this->modelFactory
        );
    }

    public function testRunRendersList(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $browse     = $this->mock(Browse::class);

        $objects = ['some-object'];

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $browse->shouldReceive('reset_filters')
            ->withNoArgs()
            ->once();
        $browse->shouldReceive('set_type')
            ->with('user')
            ->once();
        $browse->shouldReceive('set_simple_browse')
            ->with(true)
            ->once();
        $browse->shouldReceive('set_sort')
            ->with('name', 'ASC')
            ->once();
        $browse->shouldReceive('show_objects')
            ->with($objects)
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn($objects);
        $browse->shouldReceive('store')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
