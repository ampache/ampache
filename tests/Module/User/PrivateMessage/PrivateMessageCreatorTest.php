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

namespace Ampache\Module\User\PrivateMessage;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PreferenceRepositoryInterface;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class PrivateMessageCreatorTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private PrivateMessageRepositoryInterface&MockObject $pmRepository;
    private PrivateMessageCreator $subject;
    private UtilityFactoryInterface&MockObject $utilityFactory;

    public function testCreatePersistsMessageForRecipient(): void
    {
        // NOTE: the "send notification email" branch is gated by the static Preference::get_by_user() call.
        $sender    = $this->createMock(User::class);
        $recipient = $this->createMock(User::class);

        $recipient->method('getId')
            ->willReturn(42);

        $this->pmRepository->expects(static::once())
            ->method('create')
            ->with($recipient, $sender, 'subject', 'message')
            ->willReturn(21);

        $this->subject->create($recipient, $sender, 'subject', 'message');
    }

    public function testCreatePersistsMessageWithNoRecipient(): void
    {
        $sender = $this->createMock(User::class);

        $this->pmRepository->expects(static::once())
            ->method('create')
            ->with(null, $sender, 'subject', 'message')
            ->willReturn(21);

        $this->utilityFactory->expects(static::never())
            ->method('createMailer');

        $this->subject->create(null, $sender, 'subject', 'message');
    }

    protected function setUp(): void
    {
        // `Preference` reads through the `global $dic` bridge
        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            PreferenceRepositoryInterface::class => $this->createMock(PreferenceRepositoryInterface::class),
            default => $this->createMock(LoggerInterface::class),
        });

        $GLOBALS['dic'] = $dic;

        $this->pmRepository    = $this->createMock(PrivateMessageRepositoryInterface::class);
        $this->utilityFactory  = $this->createMock(UtilityFactoryInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new PrivateMessageCreator(
            $this->pmRepository,
            $this->utilityFactory,
            $this->configContainer,
        );
    }
}
