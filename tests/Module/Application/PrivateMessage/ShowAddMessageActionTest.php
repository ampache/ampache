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

namespace Ampache\Module\Application\PrivateMessage;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PrivateMessageInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowAddMessageActionTest extends MockeryTestCase
{
    private ConfigContainerInterface&MockInterface $configContainer;

    private UiInterface&MockInterface $ui;

    private ModelFactoryInterface&MockInterface $modelFactory;

    private PrivateMessageRepositoryInterface&MockInterface $privateMessageRepository;

    private ShowAddMessageAction $subject;

    protected function setUp(): void
    {
        $this->configContainer          = $this->mock(ConfigContainerInterface::class);
        $this->ui                       = $this->mock(UiInterface::class);
        $this->modelFactory             = $this->mock(ModelFactoryInterface::class);
        $this->privateMessageRepository = $this->mock(PrivateMessageRepositoryInterface::class);

        $this->subject = new ShowAddMessageAction(
            $this->configContainer,
            $this->ui,
            $this->modelFactory,
            $this->privateMessageRepository
        );
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied: sociable features are not enabled.');

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunThrowsExceptionIfSocialFeatureAreDisabled(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied: sociable features are not enabled.');

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunRenders(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with('show_add_pvmsg.inc.php')
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunRendersWithNotOwnedReply(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $replyToMessageId = 666;

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with('show_add_pvmsg.inc.php')
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['reply_to' => (string) $replyToMessageId]);

        $this->privateMessageRepository->shouldReceive('findById')
            ->with($replyToMessageId)
            ->once()
            ->andReturnNull();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunRendersWithReplyTo(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $message    = $this->mock(PrivateMessageInterface::class);
        $senderUser = $this->mock(User::class);

        $replyToMessageId = 666;
        $userId           = 42;
        $senderUserId     = 33;
        $subject          = 'some-subject';
        $messageText      = 'some-message';
        $senderUserName   = 'some-sender-username';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnTrue();
        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with('show_add_pvmsg.inc.php')
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['reply_to' => (string) $replyToMessageId]);

        $this->privateMessageRepository->shouldReceive('findById')
            ->with($replyToMessageId)
            ->once()
            ->andReturn($message);

        $message->shouldReceive('getSenderUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($senderUserId);
        $message->shouldReceive('getRecipientUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $message->shouldReceive('getSubject')
            ->withNoArgs()
            ->once()
            ->andReturn($subject);
        $message->shouldReceive('getMessage')
            ->withNoArgs()
            ->once()
            ->andReturn($messageText);

        $this->modelFactory->shouldReceive('createUser')
            ->with($senderUserId)
            ->once()
            ->andReturn($senderUser);

        $senderUser->username = $senderUserName;

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
        $this->assertStringContainsString(
            $senderUserName,
            $_REQUEST['to_user']
        );
        $this->assertStringContainsString(
            sprintf("RE: %s", $subject),
            $_REQUEST['subject']
        );
        $this->assertStringContainsString(
            $messageText,
            $_REQUEST['message']
        );
    }
}
