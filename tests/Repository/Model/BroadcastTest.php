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

use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Repository\BroadcastRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class BroadcastTest extends TestCase
{
    private BroadcastRepositoryInterface&MockObject $broadcastRepository;
    private ContainerInterface&MockObject $dic;

    public function testCreateRefusesAnEmptyName(): void
    {
        $this->broadcastRepository->expects(static::never())
            ->method('create');

        static::assertSame(0, Broadcast::create(''));
    }

    public function testDeleteDelegatesToTheRepository(): void
    {
        $subject = new Broadcast();

        $subject->id = 666;

        $this->broadcastRepository->expects(static::once())
            ->method('delete')
            ->with($subject);

        static::assertTrue($subject->delete());
    }

    public function testDeleteReturnsFalseIfTheWriteFailed(): void
    {
        $subject = new Broadcast();

        $subject->id = 666;

        $this->broadcastRepository->expects(static::once())
            ->method('delete')
            ->willThrowException(new QueryFailedException('some-error'));

        static::assertFalse($subject->delete());
    }

    public function testGetBroadcastsDelegatesToTheRepository(): void
    {
        $this->broadcastRepository->expects(static::once())
            ->method('getIdsByUser')
            ->with(42)
            ->willReturn([1, 2]);

        static::assertSame([1, 2], Broadcast::get_broadcasts(42));
    }

    public function testUpdateAppliesTheDataAndPersists(): void
    {
        $subject = new Broadcast();

        $subject->id = 666;

        $this->broadcastRepository->expects(static::once())
            ->method('update')
            ->with($subject);

        static::assertSame(
            666,
            $subject->update([
                'name' => 'some-name',
                'description' => 'some-description',
                'private' => '1',
            ])
        );

        static::assertSame('some-name', $subject->name);
        static::assertSame('some-description', $subject->description);
        static::assertTrue($subject->is_private);
    }

    public function testUpdateClearsTheDescriptionAndPrivacyWhenNotSupplied(): void
    {
        $subject = new Broadcast();

        $subject->id          = 666;
        $subject->description = 'old-description';
        $subject->is_private  = true;

        $this->broadcastRepository->expects(static::once())
            ->method('update');

        $subject->update(['name' => 'some-name']);

        static::assertSame('', $subject->description);
        static::assertFalse($subject->is_private);
    }

    public function testUpdateKeepsTheCurrentNameWhenNoneIsSupplied(): void
    {
        $subject = new Broadcast();

        $subject->id   = 666;
        $subject->name = 'old-name';

        $this->broadcastRepository->expects(static::once())
            ->method('update');

        $subject->update(['description' => 'some-description']);

        static::assertSame('old-name', $subject->name);
    }

    public function testUpdateListenersStoresTheCountOnTheObject(): void
    {
        $subject = new Broadcast();

        $subject->id = 666;

        $this->broadcastRepository->expects(static::once())
            ->method('updateListeners')
            ->with($subject, 12);

        $subject->update_listeners(12);

        static::assertSame(12, $subject->listeners);
    }

    public function testUpdateSongResetsThePosition(): void
    {
        $subject = new Broadcast();

        $subject->id            = 666;
        $subject->song_position = 5;

        $this->broadcastRepository->expects(static::once())
            ->method('updateSong')
            ->with($subject, 33);

        $subject->update_song(33);

        static::assertSame(33, $subject->song);
        static::assertSame(0, $subject->song_position);
    }

    public function testUpdateStateStoresTheStartTime(): void
    {
        $subject = new Broadcast();

        $subject->id = 666;

        $this->broadcastRepository->expects(static::once())
            ->method('updateState')
            ->with($subject, 1234, 'some-key');

        $subject->update_state(1234, 'some-key');

        static::assertSame(1234, $subject->started);
    }

    protected function setUp(): void
    {
        $this->broadcastRepository = $this->createMock(BroadcastRepositoryInterface::class);
        $this->dic                 = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')
            ->with(BroadcastRepositoryInterface::class)
            ->willReturn($this->broadcastRepository);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;
    }
}
