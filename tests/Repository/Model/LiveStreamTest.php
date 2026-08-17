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
 */

namespace Ampache\Repository\Model;

use Ampache\Module\System\AmpError;
use Ampache\Repository\LiveStreamRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

class LiveStreamTest extends TestCase
{
    private ContainerInterface&MockObject $dic;
    private LiveStreamRepositoryInterface&MockObject $liveStreamRepository;

    public function testIsNewReturnsTrueForAnUnsavedItem(): void
    {
        $subject = new Live_Stream();

        self::assertTrue($subject->isNew());
        self::assertSame(0, $subject->getId());
        self::assertSame(LibraryItemEnum::LIVE_STREAM, $subject->getMediaType());
    }

    public function testSaveAdoptsTheIdOfANewlyInsertedItem(): void
    {
        $subject = new Live_Stream();

        $this->liveStreamRepository->expects(static::once())
            ->method('persist')
            ->with($subject)
            ->willReturn(666);

        $subject->save();

        self::assertSame(666, $subject->getId());
    }

    public function testUpdateAppliesTheDataAndPersists(): void
    {
        $subject = new Live_Stream();

        $subject->id = 666;

        $this->liveStreamRepository->expects(static::once())
            ->method('persist')
            ->with($subject)
            ->willReturn(null);

        self::assertSame(
            666,
            $subject->update([
                'name' => 'some-name',
                'site_url' => 'https://some-site',
                'url' => 'https://some-url',
                'codec' => 'MP3',
            ])
        );

        self::assertSame('some-name', $subject->name);
        self::assertSame('https://some-site', $subject->site_url);
        self::assertSame('https://some-url', $subject->url);
        // the codec is stored lower case regardless of how the caller spelled it
        self::assertSame('mp3', $subject->codec);
    }

    public function testUpdateRejectsAnUnsupportedUrlSchemeWithoutPersisting(): void
    {
        $subject = new Live_Stream();

        $subject->id = 666;

        $this->liveStreamRepository->expects(static::never())
            ->method('persist');

        self::assertNull(
            $subject->update([
                'name' => 'some-name',
                'url' => 'ftp://some-url',
                'codec' => 'mp3',
            ])
        );
    }

    protected function setUp(): void
    {
        $this->liveStreamRepository = $this->createMock(LiveStreamRepositoryInterface::class);
        $this->dic                  = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')
            ->with(LiveStreamRepositoryInterface::class)
            ->willReturn($this->liveStreamRepository);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;

        // AmpError keeps its state in private statics with no public reset, so a validation failure
        // in one test would otherwise make every later assertion see an error that already occurred
        new ReflectionProperty(AmpError::class, 'errors')->setValue(null, []);
        new ReflectionProperty(AmpError::class, 'state')->setValue(null, false);
    }
}
