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
 */

declare(strict_types=1);

namespace Ampache\Module\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ShareInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class ShareValidatorTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface */
    private MockInterface $configContainer;

    /** @var MockInterface|LoggerInterface */
    private MockInterface $logger;

    private ShareValidator $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);

        $this->subject = new ShareValidator(
            $this->configContainer,
            $this->logger
        );
    }

    public function testIsValidReturnsFalseIfShareIsInvalid(): void
    {
        $share = $this->mock(ShareInterface::class);

        $secret = 'some-secret';
        $action = 'some-action';

        $share->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn(0);

        $this->logger->shouldReceive('error')
            ->with(
                'Access Denied: Invalid share.',
                [LegacyLogger::CONTEXT_TYPE => ShareValidator::class]
            )
            ->once();

        $this->assertFalse(
            $this->subject->isValid($share, $secret, $action)
        );
    }

    public function testIsValidReturnsFalseIfSharingIsDisabled(): void
    {
        $share = $this->mock(ShareInterface::class);

        $secret = 'some-secret';
        $action = 'some-action';

        $share->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn(666);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnFalse();

        $this->logger->shouldReceive('error')
            ->with(
                'Access Denied: share feature disabled.',
                [LegacyLogger::CONTEXT_TYPE => ShareValidator::class]
            )
            ->once();

        $this->assertFalse(
            $this->subject->isValid($share, $secret, $action)
        );
    }

    public function testIsValidReturnsFalseIfExpired(): void
    {
        $share = $this->mock(ShareInterface::class);

        $secret = 'some-secret';
        $action = 'some-action';

        $share->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn(666);
        $share->shouldReceive('getCreationDate')
            ->withNoArgs()
            ->once()
            ->andReturn(111);
        $share->shouldReceive('getExpireDays')
            ->withNoArgs()
            ->once()
            ->andReturn(10);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->logger->shouldReceive('error')
            ->with(
                'Access Denied: share expired.',
                [LegacyLogger::CONTEXT_TYPE => ShareValidator::class]
            )
            ->once();

        $this->assertFalse(
            $this->subject->isValid($share, $secret, $action)
        );
    }

    public function testIsValidReturnsFalseIfMaxCountReached(): void
    {
        $share = $this->mock(ShareInterface::class);

        $secret = 'some-secret';
        $action = 'some-action';

        $share->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn(666);
        $share->shouldReceive('getCreationDate')
            ->withNoArgs()
            ->once()
            ->andReturn(time());
        $share->shouldReceive('getExpireDays')
            ->withNoArgs()
            ->once()
            ->andReturn(10);
        $share->shouldReceive('getMaxCounter')
            ->withNoArgs()
            ->once()
            ->andReturn(1);
        $share->shouldReceive('getCounter')
            ->withNoArgs()
            ->once()
            ->andReturn(2);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->logger->shouldReceive('error')
            ->with(
                'Access Denied: max counter reached.',
                [LegacyLogger::CONTEXT_TYPE => ShareValidator::class]
            )
            ->once();

        $this->assertFalse(
            $this->subject->isValid($share, $secret, $action)
        );
    }

    public function testIsValidReturnsFalseIfSecretDoesNotMatch(): void
    {
        $share = $this->mock(ShareInterface::class);

        $secret  = 'some-secret';
        $action  = 'some-action';
        $shareId = 666;

        $share->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($shareId);
        $share->shouldReceive('getCreationDate')
            ->withNoArgs()
            ->once()
            ->andReturn(time());
        $share->shouldReceive('getExpireDays')
            ->withNoArgs()
            ->once()
            ->andReturn(10);
        $share->shouldReceive('getMaxCounter')
            ->withNoArgs()
            ->once()
            ->andReturn(1);
        $share->shouldReceive('getCounter')
            ->withNoArgs()
            ->once()
            ->andReturn(0);
        $share->shouldReceive('getSecret')
            ->withNoArgs()
            ->once()
            ->andReturn('abc');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->logger->shouldReceive('error')
            ->with(
                sprintf('Access Denied: secret requires to access share %s', $shareId),
                [LegacyLogger::CONTEXT_TYPE => ShareValidator::class]
            )
            ->once();

        $this->assertFalse(
            $this->subject->isValid($share, $secret, $action)
        );
    }

    public function testIsValidReturnsFalseIfDownloadIsDisabled(): void
    {
        $share = $this->mock(ShareInterface::class);

        $secret  = 'some-secret';
        $action  = 'download';
        $shareId = 666;

        $share->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($shareId);
        $share->shouldReceive('getCreationDate')
            ->withNoArgs()
            ->once()
            ->andReturn(time());
        $share->shouldReceive('getExpireDays')
            ->withNoArgs()
            ->once()
            ->andReturn(10);
        $share->shouldReceive('getMaxCounter')
            ->withNoArgs()
            ->once()
            ->andReturn(1);
        $share->shouldReceive('getCounter')
            ->withNoArgs()
            ->once()
            ->andReturn(0);
        $share->shouldReceive('getSecret')
            ->withNoArgs()
            ->once()
            ->andReturn($secret);
        $share->shouldReceive('getAllowDownload')
            ->withNoArgs()
            ->once()
            ->andReturn(0);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DOWNLOAD)
            ->once()
            ->andReturnTrue();

        $this->logger->shouldReceive('error')
            ->with(
                'Access Denied: download unauthorized.',
                [LegacyLogger::CONTEXT_TYPE => ShareValidator::class]
            )
            ->once();

        $this->assertFalse(
            $this->subject->isValid($share, $secret, $action)
        );
    }

    public function testIsValidReturnsFalseIfStreamIsDisabled(): void
    {
        $share = $this->mock(ShareInterface::class);

        $secret  = 'some-secret';
        $action  = 'stream';
        $shareId = 666;

        $share->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($shareId);
        $share->shouldReceive('getCreationDate')
            ->withNoArgs()
            ->once()
            ->andReturn(time());
        $share->shouldReceive('getExpireDays')
            ->withNoArgs()
            ->once()
            ->andReturn(10);
        $share->shouldReceive('getMaxCounter')
            ->withNoArgs()
            ->once()
            ->andReturn(1);
        $share->shouldReceive('getCounter')
            ->withNoArgs()
            ->once()
            ->andReturn(0);
        $share->shouldReceive('getSecret')
            ->withNoArgs()
            ->once()
            ->andReturn($secret);
        $share->shouldReceive('getAllowStream')
            ->withNoArgs()
            ->once()
            ->andReturn(0);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->logger->shouldReceive('error')
            ->with(
                'Access Denied: stream unauthorized.',
                [LegacyLogger::CONTEXT_TYPE => ShareValidator::class]
            )
            ->once();

        $this->assertFalse(
            $this->subject->isValid($share, $secret, $action)
        );
    }

    public function testIsValidReturnsTrue(): void
    {
        $share = $this->mock(ShareInterface::class);

        $secret  = 'some-secret';
        $action  = 'snosnoo';
        $shareId = 666;

        $share->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($shareId);
        $share->shouldReceive('getCreationDate')
            ->withNoArgs()
            ->once()
            ->andReturn(time());
        $share->shouldReceive('getExpireDays')
            ->withNoArgs()
            ->once()
            ->andReturn(10);
        $share->shouldReceive('getMaxCounter')
            ->withNoArgs()
            ->once()
            ->andReturn(1);
        $share->shouldReceive('getCounter')
            ->withNoArgs()
            ->once()
            ->andReturn(0);
        $share->shouldReceive('getSecret')
            ->withNoArgs()
            ->once()
            ->andReturn($secret);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->isValid($share, $secret, $action)
        );
    }
}
