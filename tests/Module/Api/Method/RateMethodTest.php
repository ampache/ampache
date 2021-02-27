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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class RateMethodTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private RateMethod $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->streamFactory   = $this->mock(StreamFactoryInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);

        $this->subject = new RateMethod(
            $this->configContainer,
            $this->streamFactory,
            $this->modelFactory
        );
    }

    public function testHandleThrowsExceptionIfRatingIsDisabled(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: ratings');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RATINGS)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    /**
     * @dataProvider requestDataProvider
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

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RATINGS)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            $input
        );
    }

    public function requestDataProvider(): array
    {
        return [
            [[], 'type'],
            [['type' => 1], 'id'],
            [['type' => 1, 'id' => 1], 'rating']
        ];
    }

    public function testHandleThrowsExceptionIfTypeNotAllowed(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $type = 'some_type';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $type));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RATINGS)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'id' => 1,
                'rating' => 666,
                'type' => $type
            ]
        );
    }

    public function testHandleThrowsExceptionIfRatingValueIsOutOfBounds(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $type        = 'song';
        $ratingValue = 666;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %d', $ratingValue));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RATINGS)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'id' => 1,
                'rating' => $ratingValue,
                'type' => $type
            ]
        );
    }

    public function testHandleThrowsExceptionIfItemDoesNotExist(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $type        = 'song';
        $ratingValue = 4;
        $objectId    = 42;

        $song->id = null;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RATINGS)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'id' => $objectId,
                'rating' => $ratingValue,
                'type' => $type
            ]
        );
    }

    public function testHandleReturnsResultAfterRating(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);
        $rating     = $this->mock(Rating::class);

        $type        = 'song';
        $ratingValue = 4;
        $objectId    = 42;
        $userId      = 33;
        $result      = 'some-result';

        $song->id = $objectId;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RATINGS)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);
        $this->modelFactory->shouldReceive('createRating')
            ->with($objectId, $type)
            ->once()
            ->andReturn($rating);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $rating->shouldReceive('set_rating')
            ->with($ratingValue, $userId)
            ->once();

        $output->shouldReceive('success')
            ->with(sprintf('rating set to %s for %d', $ratingValue, $objectId))
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
                    'id' => $objectId,
                    'rating' => $ratingValue,
                    'type' => $type
                ]
            )
        );
    }
}
