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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class DeleteStreamTokenActionTest extends TestCase
{
    use UserAdminAccessTestTrait;

    private RequestParserInterface&MockObject $requestParser;

    private UiInterface&MockObject $ui;

    private ModelFactoryInterface&MockObject $modelFactory;

    private ConfigContainerInterface&MockObject $configContainer;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    private ServerRequestInterface&MockObject $request;

    private DeleteStreamTokenAction $subject;

    protected function setUp(): void
    {
        $this->requestParser   = $this->createMock(RequestParserInterface::class);
        $this->ui              = $this->createMock(UiInterface::class);
        $this->modelFactory    = $this->createMock(ModelFactoryInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->gatekeeper      = $this->createMock(GuiGatekeeperInterface::class);
        $this->request         = $this->createMock(ServerRequestInterface::class);

        $this->subject = new DeleteStreamTokenAction(
            $this->requestParser,
            $this->ui,
            $this->modelFactory,
            $this->configContainer,
        );
    }

    public function testRunErrorsIfUserWasNotFound(): void
    {
        $userId = 666;

        $user = $this->createMock(User::class);

        static::expectException(ObjectNotFoundException::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(true);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(false);

        $this->requestParser->expects(static::once())
            ->method('verifyForm')
            ->with($this->getValidationFormName())
            ->willReturn(true);

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['user_id' => (string) $userId]);

        $this->modelFactory->expects(static::once())
            ->method('createUser')
            ->with($userId)
            ->willReturn($user);

        $user->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunDeletesStreamToken(): void
    {
        $userId = 666;

        $user = $this->createMock(User::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(true);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(false);

        $this->requestParser->expects(static::once())
            ->method('verifyForm')
            ->with($this->getValidationFormName())
            ->willReturn(true);

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['user_id' => (string) $userId]);

        $this->modelFactory->expects(static::once())
            ->method('createUser')
            ->with($userId)
            ->willReturn($user);

        $user->expects(static::once())
            ->method('deleteStreamToken');
        $user->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showConfirmation')
            ->with(
                'No Problem',
                'Token has been deleted',
                'admin/users.php',
            );
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    protected function getValidationFormName(): string
    {
        return 'delete_streamtoken';
    }
}
