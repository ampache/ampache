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

namespace Ampache\Module\Api\Method;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GetLyricsMethodTest extends MockeryTestCase
{
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?GetLyricsMethod $subject;

    // the web lyrics refuse a song the caller is not shown, so the api must not hand its text out either
    public function testHandleRefusesASongTheCallerIsNotShown(): void
    {
        $output   = $this->mock(ApiOutputInterface::class);
        $response = $this->mock(ResponseInterface::class);
        $stream   = $this->mock(StreamInterface::class);
        $user     = $this->mock(User::class);
        $song     = $this->mock(Song::class);

        $this->modelFactory->shouldReceive('createSong')
            ->with(666)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $song->shouldReceive('isShownTo')
            ->with($user)
            ->once()
            ->andReturnFalse();
        $song->shouldReceive('get_lyrics')
            ->never();

        $output->shouldReceive('error')
            ->with(8, ErrorCodeEnum::BAD_REQUEST, 'Bad Request: 666', GetLyricsMethod::ACTION, 'filter')
            ->once()
            ->andReturn('some-error');

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')
            ->with('some-error')
            ->once();

        self::assertSame(
            $response,
            $this->subject->handle(
                $this->mock(GatekeeperInterface::class),
                $response,
                $output,
                ['filter' => '666', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                8
            )
        );
    }

    public function testHandleReturnsTheLyricsOfASongTheCallerIsShown(): void
    {
        $output   = $this->mock(ApiOutputInterface::class);
        $response = $this->mock(ResponseInterface::class);
        $stream   = $this->mock(StreamInterface::class);
        $user     = $this->mock(User::class);
        $song     = $this->mock(Song::class);

        $this->modelFactory->shouldReceive('createSong')
            ->with(666)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $song->shouldReceive('isShownTo')
            ->with($user)
            ->once()
            ->andReturnTrue();
        $song->shouldReceive('fill_ext_info')
            ->withNoArgs()
            ->once();
        $song->shouldReceive('get_lyrics')
            ->with(true)
            ->once()
            ->andReturn(['text' => 'some-lyrics']);

        $output->shouldReceive('keyedArray')
            ->with(8, [
                'object_id' => '666',
                'object_type' => 'song',
                'plugin' => ['database' => ['text' => 'some-lyrics']],
            ])
            ->once()
            ->andReturn('some-result');

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')
            ->with('some-result')
            ->once();

        self::assertSame(
            $response,
            $this->subject->handle(
                $this->mock(GatekeeperInterface::class),
                $response,
                $output,
                ['filter' => '666', 'plugins' => 0, 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                8
            )
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new GetLyricsMethod(
            $this->modelFactory
        );
    }
}
