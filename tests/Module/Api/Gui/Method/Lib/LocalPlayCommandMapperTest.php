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

namespace Ampache\Module\Api\Gui\Method\Lib;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Mockery\MockInterface;

class LocalPlayCommandMapperTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configContainer;

    private LocalPlayCommandMapper $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new LocalPlayCommandMapper(
            $this->configContainer
        );
    }

    public function testAddCommandThrowsExceptionOnVideoModeAndIfVideoIsNotEnabled(): void
    {
        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: video');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ALLOW_VIDEO)
            ->once()
            ->andReturnFalse();

        $callable = $this->subject->map('add');

        $callable(
            $this->mock(LocalPlay::class),
            666,
            'video',
            1
        );
    }

    /**
     * @dataProvider localPlayCommandDataProvider
     */
    public function testLocalplayCommandMapping(
        string $command,
        string $method
    ): void {
        $localplay = $this->mock(LocalPlay::class);

        $localplay->shouldReceive($method)
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->map($command)($localplay)
        );
    }

    public function localPlayCommandDataProvider(): array
    {
        return [
            ['skip', 'next'],
            ['next', 'next'],
            ['prev', 'prev'],
            ['stop', 'stop'],
            ['play', 'play'],
            ['pause', 'pause'],
            ['volume_up', 'volume_up'],
            ['volume_down', 'volume_down'],
            ['volume_mute', 'volume_mute'],
            ['delete_all', 'delete_all'],
            ['status', 'status'],
        ];
    }
}
