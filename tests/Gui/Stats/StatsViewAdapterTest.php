<?php
/*
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

declare(strict_types=1);

namespace Ampache\Gui\Stats;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;
use Mockery\MockInterface;

class StatsViewAdapterTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private ?MockInterface $configContainer;

    /** @var GuiFactoryInterface|MockInterface|null */
    private ?MockInterface $guiFactory;

    /** @var MockInterface|VideoRepositoryInterface|null */
    private ?MockInterface $videoRepository;

    private ?StatsViewAdapter $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->guiFactory      = $this->mock(GuiFactoryInterface::class);
        $this->videoRepository = $this->mock(VideoRepositoryInterface::class);

        $this->subject = new StatsViewAdapter(
            $this->configContainer,
            $this->guiFactory,
            $this->videoRepository
        );
    }

    public function testDisplayVideoReturnsTrueIfItemsExist(): void
    {
        $this->videoRepository->shouldReceive('getItemCount')
            ->with(Video::class)
            ->once()
            ->andReturn(42);

        $this->assertTrue(
            $this->subject->displayVideo()
        );
    }

    public function testDisplayPodcastReturnsValue(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->displayPodcast()
        );
    }
}
