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

namespace Ampache\Module\Util\Rss;

use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Util\Rss\Surrogate\RssItemInterface;
use Ampache\Repository\Model\User;
use PhpTal\PHPTAL;
use PhpTal\PhpTalInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RssPodcastBuilderTest extends TestCase
{
    private TalFactoryInterface&MockObject $talFactory;

    private RssPodcastBuilder $subject;

    protected function setUp(): void
    {
        $this->talFactory = $this->createMock(TalFactoryInterface::class);

        $this->subject = new RssPodcastBuilder(
            $this->talFactory
        );
    }

    public function testBuildRendersPodcast(): void
    {
        $item = $this->createMock(RssItemInterface::class);
        $user = $this->createMock(User::class);
        $tal  = $this->createMock(PhpTalInterface::class);

        $result = 'some-podcast';

        $this->talFactory->expects(static::once())
            ->method('createPhpTal')
            ->willReturn($tal);

        $tal->expects(static::once())
            ->method('setOutputMode')
            ->with(PHPTAL::XML);
        $tal->expects(static::once())
            ->method('setTemplate')
            ->with(realpath(__DIR__ . '/../../../../resources/templates/rss/podcast.xml'));
        $tal->expects(static::once())
            ->method('set')
            ->with('THIS', $item);
        $tal->expects(static::once())
            ->method('execute')
            ->willReturn($result);

        static::assertSame(
            $result,
            $this->subject->build($item, $user)
        );
    }
}
