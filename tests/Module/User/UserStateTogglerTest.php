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
    private MockInterface&ConfigContainerInterface $configContainer;

    private MockInterface&UtilityFactoryInterface $utilityFactory;

    private MockInterface&UserRepositoryInterface $userRepository;

    private UserStateToggler $subject;

    protected function setUp(): void
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

        $userId         = 666;
        $user->email    = 'example@email.com';
        $user->fullname = 'name';

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

        $userName       = 'some-name';
        $userId         = 666;
        $email          = 'example@email.com';
        $fullName       = 'some-fullname';
        $siteTitle      = 'some-title';
        $webPath        = 'some-path';
        $message        = sprintf('A new user has been enabled. %s', $userName) .
            "\n\n" .
            sprintf(
                'You can log in at the following address %s',
                $webPath
            );

        $this->userRepository->shouldReceive('enable')
            ->with($userId)
            ->once();

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $user->shouldReceive('getUsername')
            ->withNoArgs()
            ->once()
            ->andReturn($userName);
        $user->shouldReceive('get_fullname')
            ->withNoArgs()
            ->once()
            ->andReturn($fullName);
        $user->email = $email;

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
            ->andReturn($webPath);
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SITE_TITLE)
            ->once()
            ->andReturn($siteTitle);

        $mailer->shouldReceive('set_default_sender')
            ->withNoArgs()
            ->once()
            ->andReturnSelf();
        $mailer->shouldReceive('setSubject')
            ->with(sprintf(
                'Account enabled at %s',
                $siteTitle
            ))
            ->once()
            ->andReturnSelf();
        $mailer->shouldReceive('setMessage')
            ->with($message)
            ->once()
            ->andReturnSelf();
        $mailer->shouldReceive('setRecipient')
            ->with($email, $fullName)
            ->once()
            ->andReturnSelf();
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
