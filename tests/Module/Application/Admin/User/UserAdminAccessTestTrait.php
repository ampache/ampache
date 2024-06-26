<?php

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

trait UserAdminAccessTestTrait
{
    private GuiGatekeeperInterface&MockObject $gatekeeper;

    private ServerRequestInterface&MockObject $request;

    public function testHandleThrowsIfAccessIsDenied(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(false);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testHandleReturnsNullIfDemoMode(): void
    {
        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(true);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(true);

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testFormValidationErrorsIfInvalid(): void
    {
        static::expectException(AccessDeniedException::class);

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
            ->willReturn(false);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    /**
     * Returns the name of the form to verify after submit
     */
    abstract protected function getValidationFormName(): string;
}
