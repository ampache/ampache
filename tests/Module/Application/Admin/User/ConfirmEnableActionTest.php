<?php

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ConfirmEnableActionTest extends TestCase
{
    use UserAdminAccessTestTrait;

    private RequestParserInterface&MockObject $requestParser;

    private UiInterface&MockObject $ui;

    private ModelFactoryInterface&MockObject $modelFactory;

    private ConfigContainerInterface&MockObject $configContainer;

    private UserStateTogglerInterface&MockObject $userStateToggler;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    private ServerRequestInterface&MockObject $request;

    private ConfirmEnableAction $subject;

    protected function setUp(): void
    {
        $this->requestParser    = $this->createMock(RequestParserInterface::class);
        $this->ui               = $this->createMock(UiInterface::class);
        $this->modelFactory     = $this->createMock(ModelFactoryInterface::class);
        $this->configContainer  = $this->createMock(ConfigContainerInterface::class);
        $this->userStateToggler = $this->createMock(UserStateTogglerInterface::class);

        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $this->request    = $this->createMock(ServerRequestInterface::class);

        $this->subject = new ConfirmEnableAction(
            $this->requestParser,
            $this->ui,
            $this->modelFactory,
            $this->configContainer,
            $this->userStateToggler,
        );
    }

    protected function getValidationFormName(): string
    {
        return 'enable_user';
    }

    public function testRunErrorsIfTheUserIsNew(): void
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

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunEnables(): void
    {
        $userId   = 666;
        $userName = 'some-name';

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
            ->method('getFullDisplayName')
            ->willReturn($userName);
        $user->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        $this->userStateToggler->expects(static::once())
            ->method('enable')
            ->with($user);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showConfirmation')
            ->with(
                'No Problem',
                sprintf('%s has been enabled', $userName),
                '/users.php'
            );
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }
}
