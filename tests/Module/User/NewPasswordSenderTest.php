<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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
 */

namespace Ampache\Module\User;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewPasswordSenderTest extends TestCase
{
    private PasswordGeneratorInterface&MockObject $passwordGenerator;
    private NewPasswordSender $subject;
    private UserRepositoryInterface&MockObject $userRepository;

    public function testSendReturnsFalseForAdministrators(): void
    {
        // NOTE: Mailer calls Mailer::is_mail_enabled() with no injectable.
        $user = $this->createMock(User::class);

        $user->method('has_access')
            ->with(AccessLevelEnum::ADMIN)
            ->willReturn(true);

        $this->userRepository->expects(static::once())
            ->method('findByEmail')
            ->with('admin@example.com')
            ->willReturn($user);

        $this->passwordGenerator->expects(static::never())
            ->method('generate');

        self::assertFalse($this->subject->send('admin@example.com', '127.0.0.1'));
    }

    public function testSendReturnsFalseWhenUserNotFound(): void
    {
        $this->userRepository->expects(static::once())
            ->method('findByEmail')
            ->with('nobody@example.com')
            ->willReturn(null);

        $this->passwordGenerator->expects(static::never())
            ->method('generate');

        self::assertFalse($this->subject->send('nobody@example.com', '127.0.0.1'));
    }

    protected function setUp(): void
    {
        $this->passwordGenerator = $this->createMock(PasswordGeneratorInterface::class);
        $this->userRepository    = $this->createMock(UserRepositoryInterface::class);

        $this->subject = new NewPasswordSender(
            $this->passwordGenerator,
            $this->userRepository,
        );
    }
}
