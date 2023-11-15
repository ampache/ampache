<?php

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\User;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Psr\Http\Message\ServerRequestInterface;

trait UserAdminConfirmationTestTrait
{
    public function testHandleErrorsIfTheProvidedUserIdIsLesserThenOne(): void
    {
        $request    = $this->createMock(ServerRequestInterface::class);
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);

        $gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->willReturn(true);

        $request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['user_id' => (string) -1]);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        static::expectOutputString('You have requested an object that does not exist');

        static::assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    /**
     * @param callable(int): void $confirmationExpectationsCallback
     */
    private function createConfirmationExpectations(
        callable $confirmationExpectationsCallback
    ): void {
        $request    = $this->createMock(ServerRequestInterface::class);
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);

        $userId = 666;

        $gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->willReturn(true);

        $request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['user_id' => (string) $userId]);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        $confirmationExpectationsCallback($userId);

        static::assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
