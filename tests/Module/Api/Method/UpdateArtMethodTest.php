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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\ArtItemGathererInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class UpdateArtMethodTest extends MockeryTestCase
{
    /** @var ArtItemGathererInterface|MockInterface|null */
    private MockInterface $artItemGatherer;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    private UpdateArtMethod $subject;

    public function setUp(): void
    {
        $this->artItemGatherer = $this->mock(ArtItemGathererInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->streamFactory   = $this->mock(StreamFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new UpdateArtMethod(
            $this->artItemGatherer,
            $this->modelFactory,
            $this->streamFactory,
            $this->configContainer
        );
    }

    /**
     * @dataProvider requestParamDataProvider
     */
    public function testHandleThrowsExceptionIfRequestParamsMissing(
        array $input,
        string $keyName
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $keyName));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            $input
        );
    }

    public function requestParamDataProvider(): array
    {
        return [
            [[], 'type'],
            [['type' => 1], 'id'],
        ];
    }

    public function testHandleThrowsExceptionIfAccessIsDenied(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 75');

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => 'song', 'id' => 666]
        );
    }

    public function testHandleThrowsExceptionIfTypeIsNotSupported(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: song');

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => 'song', 'id' => 666]
        );
    }

    public function testHandleThrowsExceptionIfItemWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);

        $type     = 'artist';
        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($artist);

        $artist->id = 0;

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => 'artist', 'id' => (string) $objectId]
        );
    }

    public function testHandleThrowsExceptionIfUdpateFailes(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);

        $type     = 'artist';
        $objectId = 666;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %d', $objectId));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($artist);

        $artist->id = $objectId;

        $this->artItemGatherer->shouldReceive('gather')
            ->with($type, $objectId, false, true)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => 'artist', 'id' => (string) $objectId, 'overwrite' => 1]
        );
    }

    public function testHandleUpdates(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);
        $stream     = $this->mock(StreamInterface::class);

        $type     = 'artist';
        $objectId = 666;
        $webPath  = 'some-pat';
        $auth     = 'some-auth';
        $result   = 'some-result';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();
        $gatekeeper->shouldReceive('getAuth')
            ->withNoArgs()
            ->once()
            ->andReturn($auth);

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($artist);

        $artist->id = $objectId;

        $this->artItemGatherer->shouldReceive('gather')
            ->with($type, $objectId, false, true)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $output->shouldReceive('success')
            ->with(
                sprintf('Gathered new art for: %d (%s)', $objectId, $type),
                [
                    'art' => sprintf(
                        '%s/image.php?object_id=%d&object_type=artist&auth=%s',
                        $webPath,
                        $objectId,
                        $auth
                    )
                ]
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'type' => 'artist',
                    'id' => (string) $objectId,
                    'overwrite' => 1,
                ]
            )
        );
    }
}
