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

namespace Ampache\Gui\System;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Mockery\MockInterface;

class ConfigViewAdapterTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configContainer;

    private ConfigViewAdapter $subject;

    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new ConfigViewAdapter(
            $this->configContainer
        );
    }

    public function testIsWaveformEnabledReturnsValue(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::WAVEFORM)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->isWaveformEnabled()
        );
    }

    public function testIsDirectplayEnabled(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DIRECTPLAY)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->isDirectplayEnabled()
        );
    }

    public function testIsLicensingEnabled(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::LICENSING)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->isLicensingEnabled()
        );
    }

    public function testIsShowSkippedTimesEnabled(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHOW_SKIPPED_TIMES)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->isShowSkippedTimesEnabled()
        );
    }

    public function testIsShowPlayedTimesEnabled(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHOW_PLAYED_TIMES)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->isShowPlayedTimesEnabled()
        );
    }

    public function testIsRatingEnabled(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RATINGS)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->isRatingEnabled()
        );
    }
}
