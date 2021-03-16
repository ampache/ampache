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

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\DemocraticRepositoryInterface;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;

class DemocraticControlMapperTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var MockInterface|DemocraticRepositoryInterface|null */
    private MockInterface $demoraticRepository;

    private DemocraticControlMapper $subject;

    public function setUp(): void
    {
        $this->modelFactory        = $this->mock(ModelFactoryInterface::class);
        $this->demoraticRepository = $this->mock(DemocraticRepositoryInterface::class);

        $this->subject = new DemocraticControlMapper(
            $this->modelFactory,
            $this->demoraticRepository
        );
    }

    public function testMapReturnsNullIfCommandIsUnknown(): void
    {
        $this->assertNull(
            $this->subject->map('foobar')
        );
    }

    public function testVoteMethodThrowsExceptionIfSongIsNew(): void
    {
        $democratic = $this->mock(Democratic::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);

        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf(T_('Not Found: %d'), $objectId));

        $this->modelFactory->shouldReceive('createSong')
            ->with($objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        call_user_func(
            $this->subject->map('vote'),
            $democratic,
            $output,
            $user,
            $objectId
        );
    }

    public function testVoteMethodReturnsData(): void
    {
        $democratic = $this->mock(Democratic::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);

        $objectId = 666;
        $result   = 'some-result';

        $this->modelFactory->shouldReceive('createSong')
            ->with($objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $democratic->shouldReceive('add_vote')
            ->with([[
                'object_type' => 'song',
                'object_id' => $objectId
            ]])
            ->once();

        $output->shouldReceive('dict')
            ->with([
                'method' => 'vote',
                'result' => true
            ])
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            call_user_func(
                $this->subject->map('vote'),
                $democratic,
                $output,
                $user,
                $objectId
            )
        );
    }

    public function testDevoteMethodThrowsExceptionIfSongIsNew(): void
    {
        $democratic = $this->mock(Democratic::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);

        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf(T_('Not Found: %d'), $objectId));

        $this->modelFactory->shouldReceive('createSong')
            ->with($objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        call_user_func(
            $this->subject->map('devote'),
            $democratic,
            $output,
            $user,
            $objectId
        );
    }

    public function testDevoteMethodReturnsData(): void
    {
        $democratic = $this->mock(Democratic::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);

        $objectId           = 666;
        $democraticObjectId = 42;
        $result             = 'some-result';

        $this->modelFactory->shouldReceive('createSong')
            ->with($objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $democratic->shouldReceive('get_uid_from_object_id')
            ->with($objectId)
            ->once()
            ->andReturn($democraticObjectId);
        $democratic->shouldReceive('remove_vote')
            ->with($democraticObjectId)
            ->once();

        $output->shouldReceive('dict')
            ->with([
                'method' => 'devote',
                'result' => true
            ])
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            call_user_func(
                $this->subject->map('devote'),
                $democratic,
                $output,
                $user,
                $objectId
            )
        );
    }

    public function testPlayReturnsUrl(): void
    {
        $democratic = $this->mock(Democratic::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $objectId = 666;
        $url      = 'some-url';
        $result   = 'some-result';

        $democratic->shouldReceive('play_url')
            ->withNoArgs()
            ->once()
            ->andReturn($url);

        $output->shouldReceive('dict')
            ->with([
                'url' => $url
            ])
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            call_user_func(
                $this->subject->map('play'),
                $democratic,
                $output,
                $user,
                $objectId
            )
        );
    }
}
