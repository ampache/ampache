<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Artist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateFromTagsActionTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    private ?UpdateFromTagsAction $subject;

    protected function setUp(): void
    {
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->ui              = $this->mock(UiInterface::class);

        $this->subject = new UpdateFromTagsAction(
            $this->modelFactory,
            $this->configContainer,
            $this->ui
        );
    }

    public function testRunRenders(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $artist     = $this->mock(Artist::class);

        $artistId = 666;
        $webPath  = 'some-web-path';

        $request->shouldReceive('getQueryPArams')
            ->withNoArgs()
            ->once()
            ->andReturn(['artist' => (string) $artistId]);

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_update_items.inc.php',
                [
                    'object_id' => $artistId,
                    'catalog_id' => null,
                    'type' => 'artist',
                    'target_url' => sprintf(
                        '%s/artists.php?action=show&artist=%d',
                        $webPath,
                        $artistId
                    )
                ]
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
