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

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class AdvancedSearchMethodTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    private AdvancedSearchMethod $subject;

    public function setUp(): void
    {
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->streamFactory   = $this->mock(StreamFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new AdvancedSearchMethod(
            $this->modelFactory,
            $this->streamFactory,
            $this->configContainer
        );
    }

    /**
     * @dataProvider requestDataProvider
     */
    public function testHandleThrowsExceptionIfRequestParamIsMissing(
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

    public function requestDataProvider(): array
    {
        return [
            [[], 'rule_1'],
            [['rule_1' => 1], 'rule_1_operator'],
            [['rule_1' => 1, 'rule_1_operator' => 1], 'rule_1_input'],
        ];
    }

    public function testHandleThrowsExceptionIfVideoAndVideoNotAllowed(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: video');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ALLOW_VIDEO)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'rule_1' => 1,
                'rule_1_operator' => 1,
                'rule_1_input' => 1,
                'type' => 'video',
            ]
        );
    }

    public function testHandleThrowsExceptionIfUnsupportedType(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: foobar');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'rule_1' => 1,
                'rule_1_operator' => 1,
                'rule_1_input' => 1,
                'type' => 'foobar',
            ]
        );
    }

    public function testHandleReturnsEmptyResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);
        $user       = $this->mock(User::class);

        $result = 'some-result';
        $type   = 'song';
        $input  = [
            'rule_1' => 1,
            'rule_1_operator' => 1,
            'rule_1_input' => 1,
            'type' => $type,
        ];

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $output->shouldReceive('emptyResult')
            ->with($type)
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

        $this->modelFactory->shouldReceive('createSearch->runSearch')
            ->with($input, $user)
            ->once()
            ->andReturn([]);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                $input
            )
        );
    }

    /**
     * @dataProvider searchDataProvider
     */
    public function testHandleReturnsResult(
        string $outputMethod,
        string $type,
        int $userId,
        array $expectations
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);
        $user       = $this->mock(User::class);

        $results = [1, 2, 3];
        $result  = 'some-result';
        $input   = [
            'rule_1' => 1,
            'rule_1_operator' => 1,
            'rule_1_input' => 1,
            'type' => $type,
        ];

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $output->shouldReceive($outputMethod)
            ->withArgs(array_merge([$results], $expectations))
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

        $this->modelFactory->shouldReceive('createSearch->runSearch')
            ->with($input, $user)
            ->once()
            ->andReturn($results);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ALLOW_VIDEO)
            ->andReturnTrue();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                $input
            )
        );
    }

    public function searchDataProvider(): array
    {
        return [
            [
                'artists',
                'artist',
                666,
                [
                    [],
                    666,
                    true,
                    true,
                    0,
                    0
                ]
            ],
            [
                'albums',
                'album',
                666,
                [
                    [],
                    666,
                    true,
                    true,
                    0,
                    0
                ]
            ],
            [
                'playlists',
                'playlist',
                666,
                [
                    666,
                    false,
                    true,
                    0,
                    0
                ]
            ],
            [
                'labels',
                'label',
                666,
                [
                    true,
                    0,
                    0
                ]
            ],
            [
                'users',
                'user',
                666,
                []
            ],
            [
                'videos',
                'video',
                666,
                [
                    666,
                    true,
                    0,
                    0
                ]
            ],
            [
                'songs',
                'song',
                666,
                [
                    666,
                    true,
                    true,
                    true,
                    0,
                    0
                ]
            ],
        ];
    }
}
