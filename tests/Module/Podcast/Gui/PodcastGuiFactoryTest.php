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

namespace Ampache\Module\Podcast\Gui;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PodcastEpisodeInterface;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Mockery\MockInterface;

class PodcastGuiFactoryTest extends MockeryTestCase
{
    /** @var MockInterface|ModelFactoryInterface */
    private MockInterface $modelFactory;

    /** @var MockInterface|PodcastEpisodeRepositoryInterface */
    private MockInterface $podcastEpisodeRepository;

    /** @var MockInterface|ConfigContainerInterface */
    private MockInterface $configContainer;

    private PodcastGuiFactory $subject;

    public function setUp(): void
    {
        $this->modelFactory             = $this->mock(ModelFactoryInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);
        $this->configContainer          = $this->mock(ConfigContainerInterface::class);

        $this->subject = new PodcastGuiFactory(
            $this->modelFactory,
            $this->podcastEpisodeRepository,
            $this->configContainer
        );
    }

    public function testCreatePodcastViewAdapterReturnsInstance(): void
    {
        $this->assertInstanceOf(
            PodcastViewAdapter::class,
            $this->subject->createPodcastViewAdapter(
                $this->mock(PodcastInterface::class)
            )
        );
    }

    public function testCreatePodcastEpisodeViewAdapterReturnsInstance(): void
    {
        $this->assertInstanceOf(
            PodcastEpisodeViewAdapter::class,
            $this->subject->createPodcastEpisodeViewAdapter(
                $this->mock(PodcastEpisodeInterface::class)
            )
        );
    }
}
