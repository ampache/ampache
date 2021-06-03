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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Application\Art;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\database_object;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ClearArtActionTest extends MockeryTestCase
{
    private MockInterface $modelFactory;

    private MockInterface $ui;

    private ClearArtAction $subject;

    public function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);
        $this->ui           = $this->mock(UiInterface::class);

        $this->subject = new ClearArtAction(
            $this->modelFactory,
            $this->ui
        );
    }

    public function testRunRendersDialog(): void
    {
        $objectType = 'song';
        $objectId   = 666;
        $burl       = 'some-url';

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $item       = $this->mock(library_item::class, database_object::class);
        $art        = $this->mock(Art::class);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'object_type' => $objectType,
                'object_id' => (string) $objectId,
                'burl' => base64_encode($burl)
            ]);

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($objectType, $objectId)
            ->once()
            ->andReturn($item);
        $this->modelFactory->shouldReceive('createArt')
            ->with($objectId, $objectType)
            ->once()
            ->andReturn($art);

        $art->shouldReceive('reset')
            ->withNoArgs()
            ->once();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $item->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $item->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($objectId);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'No Problem',
                'Art information has been removed from the database',
                $burl
            )
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
