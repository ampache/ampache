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

namespace Ampache\Module\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\User;
use Ampache\Module\Util\MailerInterface;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;

class UserStateTogglerTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configContainer;

    /** @var MockInterface|UtilityFactoryInterface|null */
    private MockInterface $utilityFactory;

    /** @var MockInterface|UserRepositoryInterface|null */
    private MockInterface $userRepository;

    private ?UserStateToggler $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->utilityFactory  = $this->mock(UtilityFactoryInterface::class);
        $this->userRepository  = $this->mock(UserRepositoryInterface::class);

        $this->subject = new UserStateToggler(
            $this->configContainer,
            $this->utilityFactory,
            $this->userRepository
        );
    }

    public function testEnableEnablesAndReturnsValue(): void
    {
        $user = $this->mock(User::class);

        $userId = 666;

        $this->userRepository->shouldReceive('enable')
            ->with($userId)
            ->once();

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_NO_EMAIL_CONFIRM)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->enable($user)
        );
    }

    /**
     * User properties are retrieved/set by direct property access so we can't set expectations...
     */
    public function testEnableEnablesAndSendsMail(): void
    {
        $user   = $this->mock(User::class);
        $mailer = $this->mock(MailerInterface::class);

        $userName = 'some-name';

        $userId = 666;

        $this->userRepository->shouldReceive('enable')
            ->with($userId)
            ->once();

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $user->username = $userName;

        $this->utilityFactory->shouldReceive('createMailer')
            ->withNoArgs()
            ->once()
            ->andReturn($mailer);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_NO_EMAIL_CONFIRM)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn('some-path');
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SITE_TITLE)
            ->once()
            ->andReturn('some-title');

        $mailer->shouldReceive('set_default_sender')
            ->withNoArgs()
            ->once();
        $mailer->shouldReceive('send')
            ->withNoArgs()
            ->once();

        $this->assertTrue(
            $this->subject->enable($user)
        );
    }

    public function testDisableDisablesAndReturnsValue(): void
    {
        $user = $this->mock(User::class);

        $user->shouldReceive('disable')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->disable($user)
        );
    }
}
