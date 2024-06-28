<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
 */

namespace Ampache\Module\Util;

use Ampache\Module\System\LegacyLogger;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[RunTestsInSeparateProcesses]
class RequestParserTest extends TestCase
{
    private LoggerInterface $logger;

    private RequestParser $subject;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new RequestParser(
            $this->logger
        );
    }

    public function testGetFromRequestReturnsEmptyStringIfNotContained(): void
    {
        static::assertSame(
            '',
            $this->subject->getFromRequest('snafu')
        );
    }

    public function testGetFromPostReturnsEmptyStringIfNotContained(): void
    {
        static::assertSame(
            '',
            $this->subject->getFromPost('snafu')
        );
    }

    public function testVerifyFormReturnsFalseIfSidNotKnown(): void
    {
        $formName = 'some-form';

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                sprintf('Form %s not found in session, rejecting request', $formName),
                [LegacyLogger::CONTEXT_TYPE => $this->subject::class]
            );

        static::assertFalse(
            $this->subject->verifyForm($formName)
        );
    }

    public function testVerifyFormReturnsFalseIfFormNameDoesNotMatchSessionData(): void
    {
        $formName = 'some-form';
        $sid      = 'some-sid';

        $_POST['form_validation'] = $sid;
        $_SESSION['forms'][$sid]  = ['name' => 'lerl'];

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                sprintf('form %s failed consistency check, rejecting request', $formName),
                [LegacyLogger::CONTEXT_TYPE => $this->subject::class]
            );

        static::assertFalse(
            $this->subject->verifyForm($formName)
        );
    }

    public function testVerifyFormReturnsFalseIfExpired(): void
    {
        $formName = 'some-form';
        $sid      = 'some-sid';

        $_POST['form_validation'] = $sid;
        $_SESSION['forms'][$sid]  = ['name' => $formName, 'expire' => time() - 1];

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                sprintf('Verified SID %s for form %s', $sid, $formName),
                [LegacyLogger::CONTEXT_TYPE => $this->subject::class]
            );
        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                sprintf('Form %s is expired, rejecting request', $formName),
                [LegacyLogger::CONTEXT_TYPE => $this->subject::class]
            );

        static::assertFalse(
            $this->subject->verifyForm($formName)
        );
    }

    public function testVerifyFormReturnsTrueIfRequestIsOk(): void
    {
        $formName = 'some-form';
        $sid      = 'some-sid';

        $_POST['form_validation'] = $sid;
        $_SESSION['forms'][$sid]  = ['name' => $formName, 'expire' => time() + 1000];

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                sprintf('Verified SID %s for form %s', $sid, $formName),
                [LegacyLogger::CONTEXT_TYPE => $this->subject::class]
            );

        static::assertTrue(
            $this->subject->verifyForm($formName)
        );
    }
}
