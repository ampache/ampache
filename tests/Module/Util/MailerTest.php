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

use Ampache\Config\AmpConfig;
use PHPUnit\Framework\TestCase;

/**
 * NOTE: Mailer has no constructor.
 */
class MailerTest extends TestCase
{
    private Mailer $subject;

    public function testFluentSettersReturnTheSameInstance(): void
    {
        self::assertSame($this->subject, $this->subject->setMessage('some message'));
        self::assertSame($this->subject, $this->subject->setRecipient('someone@example.com', 'Someone'));
        self::assertSame($this->subject, $this->subject->setSender('sender@example.com', 'Sender'));
        self::assertSame($this->subject, $this->subject->setSubject('some subject'));
    }

    public function testIsMailEnabledIsFalseInDemoMode(): void
    {
        AmpConfig::set('mail_enable', true, true);
        AmpConfig::set('demo_mode', true, true);

        self::assertFalse(Mailer::is_mail_enabled());
    }

    public function testIsMailEnabledReflectsConfig(): void
    {
        AmpConfig::set('mail_enable', true, true);
        AmpConfig::set('demo_mode', false, true);

        self::assertTrue(Mailer::is_mail_enabled());
        self::assertTrue($this->subject->isMailEnabled());
    }

    public function testSetDefaultSenderUsesConfiguredValues(): void
    {
        AmpConfig::set('mail_user', 'noreply', true);
        AmpConfig::set('mail_domain', 'ampache.test', true);
        AmpConfig::set('mail_name', 'Ampache Test', true);

        self::assertSame($this->subject, $this->subject->set_default_sender());
    }

    public function testValidateAddressAcceptsAValidEmail(): void
    {
        self::assertTrue(Mailer::validate_address('someone@example.com'));
    }

    public function testValidateAddressRejectsAnInvalidEmail(): void
    {
        self::assertFalse(Mailer::validate_address('not-an-email'));
    }

    protected function setUp(): void
    {
        $this->subject = new Mailer();
    }
}
