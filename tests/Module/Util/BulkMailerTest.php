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

namespace Ampache\Module\Util;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BulkMailerTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private BulkMailer $subject;

    public function testIsEnabledDelegatesToMailer(): void
    {
        $this->mailer->method('isMailEnabled')->willReturn(true);

        self::assertTrue($this->subject->isEnabled());
    }

    public function testSendToGroupReturnsFalseWhenMailDisabled(): void
    {
        $this->mailer->method('isMailEnabled')->willReturn(false);
        $this->mailer->expects(static::never())->method('send_to_group');

        self::assertFalse($this->subject->sendToGroup('all', 'subject', 'message'));
    }

    public function testSendToGroupSendsWhenEnabled(): void
    {
        $this->mailer->method('isMailEnabled')->willReturn(true);
        $this->mailer->method('setSubject')->with('subject')->willReturn($this->mailer);
        $this->mailer->method('setMessage')->with('message')->willReturn($this->mailer);
        $this->mailer->method('set_default_sender')->willReturn($this->mailer);
        $this->mailer->expects(static::once())
            ->method('send_to_group')
            ->with('users')
            ->willReturn(true);

        self::assertTrue($this->subject->sendToGroup('users', 'subject', 'message'));
    }

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);

        $this->subject = new BulkMailer($this->mailer);
    }
}
