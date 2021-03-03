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

namespace Ampache\Module\Application\Browse;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class CatalogActionTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    private CatalogAction $subject;

    public function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);
        $this->ui           = $this->mock(UiInterface::class);

        $this->subject = new CatalogAction(
            $this->modelFactory,
            $this->ui
        );
    }

    public function testRunUpdatesSessionAndReturnsNull(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $browse     = $this->mock(Browse::class);

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_update_session')
            ->with(true)
            ->once();
        $browse->shouldReceive('store')
            ->withNoArgs()
            ->once();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
