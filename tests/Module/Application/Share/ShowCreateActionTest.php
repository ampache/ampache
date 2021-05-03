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

namespace Ampache\Module\Application\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\User\PasswordGenerator;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowCreateActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    /** @var PasswordGeneratorInterface|MockInterface */
    private MockInterface $passwordGenerator;

    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    private ShowCreateAction $subject;

    public function setUp(): void
    {
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->ui                = $this->mock(UiInterface::class);
        $this->passwordGenerator = $this->mock(PasswordGeneratorInterface::class);
        $this->modelFactory      = $this->mock(ModelFactoryInterface::class);

        $this->subject = new ShowCreateAction(
            $this->configContainer,
            $this->ui,
            $this->passwordGenerator,
            $this->modelFactory
        );
    }

    public function testRunThrowsExceptionIfFeatureIsNotEnabled(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnFalse();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied: sharing features are not enabled.');

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunDoesNothingIfTypeNotAllowed(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunRenders(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $song       = $this->mock(Song::class);

        $objectId   = 666;
        $objectType = 'song';
        $link       = 'some-link';
        $password   = 'some-password';

        $song->f_link = $link;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'type' => $objectType,
                'id' => [(string) $objectId]
            ]);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_add_share.inc.php',
                [
                    'objectLink' => $link,
                    'secret' => $password
                ]
            )
            ->once();

        $this->passwordGenerator->shouldReceive('generate')
            ->with(PasswordGenerator::DEFAULT_LENGTH)
            ->once()
            ->andReturn($password);

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($objectType, $objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $song->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
