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
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Song\Tag;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SongTagWriterTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private LoggerInterface&MockObject $logger;
    private SongTagWriter $subject;
    private UtilityFactoryInterface&MockObject $utilityFactory;

    public function testCheckForDuplicateReplacesMatchingPicture(): void
    {
        $apics = [
            ['picturetypeid' => 8],
        ];
        $newPic = ['picturetypeid' => 8, 'data' => 'new-data', 'description' => 'cover', 'mime' => 'image/png'];
        $ndata  = [];

        $result = $this->subject->check_for_duplicate($apics, $newPic, $ndata, 'picturetypeid');

        self::assertSame(0, $result);
        self::assertSame(
            [
                'data' => 'new-data',
                'description' => 'cover',
                'mime' => 'image/png',
                'picturetypeid' => 8,
            ],
            $ndata['attached_picture'][0],
        );
    }

    public function testCheckForDuplicateReturnsNullWhenNoMatchFound(): void
    {
        $apics = [
            ['picturetypeid' => 3],
        ];
        $newPic = ['picturetypeid' => 8, 'data' => 'x', 'description' => null, 'mime' => null];
        $ndata  = [];

        $result = $this->subject->check_for_duplicate($apics, $newPic, $ndata, 'picturetypeid');

        self::assertNull($result);
        self::assertArrayNotHasKey('attached_picture', $ndata);
    }

    public function testWriteDoesNothingForANewSong(): void
    {
        $song = $this->createMock(Song::class);

        $song->method('isNew')
            ->willReturn(true);

        $this->configContainer->expects(static::never())
            ->method('isFeatureEnabled');

        $this->subject->write($song);
    }

    public function testWriteDoesNothingWhenWriteTagsFeatureIsDisabled(): void
    {
        $song = $this->createMock(Song::class);

        $song->method('isNew')
            ->willReturn(false);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::WRITE_TAGS)
            ->willReturn(false);

        $this->utilityFactory->expects(static::never())
            ->method('createVaInfo');

        $this->subject->write($song);
    }

    public function testWriteRatingDoesNothingWhenWriteTagsFeatureIsDisabled(): void
    {
        $song   = $this->createMock(Song::class);
        $user   = $this->createMock(User::class);
        $rating = $this->createMock(Rating::class);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::WRITE_TAGS)
            ->willReturn(false);

        $this->utilityFactory->expects(static::never())
            ->method('createVaInfo');

        $this->subject->writeRating($song, $user, $rating);
    }

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->utilityFactory  = $this->createMock(UtilityFactoryInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->subject = new SongTagWriter(
            $this->configContainer,
            $this->utilityFactory,
            $this->logger,
        );
    }
}
