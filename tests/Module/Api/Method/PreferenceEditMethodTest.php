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

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Preference\UserPreferenceRetrieverInterface;
use Ampache\Module\Preference\UserPreferenceUpdaterInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class PreferenceEditMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var UserPreferenceUpdaterInterface|MockInterface|null */
    private MockInterface $userPreferenceUpdater;

    /** @var UserPreferenceRetrieverInterface|MockInterface|null */
    private MockInterface $userPreferenceRetriever;

    private PreferenceEditMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory           = $this->mock(StreamFactoryInterface::class);
        $this->userPreferenceUpdater   = $this->mock(UserPreferenceUpdaterInterface::class);
        $this->userPreferenceRetriever = $this->mock(UserPreferenceRetrieverInterface::class);

        $this->subject = new PreferenceEditMethod(
            $this->streamFactory,
            $this->userPreferenceUpdater,
            $this->userPreferenceRetriever
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
            [[], 'filter'],
            [['filter' => 1], 'value'],
        ];
    }

    public function testHandleThrowsExceptionIfAccessIsDenied(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 100');

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => 666, 'value' => 42]
        );
    }

    public function testHandleThrowsExceptionIfPreferenceWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $preferenceName = 'some-preference';
        $userId         = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %s', $preferenceName));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();
        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $user->shouldReceive('fixPreferences')
            ->withNoArgs()
            ->once();

        $this->userPreferenceRetriever->shouldReceive('retrieve')
            ->with($userId, $preferenceName)
            ->once()
            ->andReturnNull();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => $preferenceName, 'value' => 42]
        );
    }

    public function testHandleThrowsExceptionIfUpdateFails(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $preferenceName = 'some-preference';
        $userId         = 666;
        $value          = 42;
        $preference     = 'some-preference-value';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request');

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();
        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $user->shouldReceive('fixPreferences')
            ->withNoArgs()
            ->once();

        $this->userPreferenceRetriever->shouldReceive('retrieve')
            ->with($userId, $preferenceName)
            ->once()
            ->andReturn($preference);

        $this->userPreferenceUpdater->shouldReceive('update')
            ->with($preferenceName, $userId, $value, true)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => $preferenceName, 'value' => $value, 'all' => 1]
        );
    }

    public function testHandleReturnsResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $preferenceName = 'some-preference';
        $userId         = 666;
        $value          = 42;
        $preference     = 'some-preference-value';
        $result         = 'some-result';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();
        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $user->shouldReceive('fixPreferences')
            ->withNoArgs()
            ->once();

        $this->userPreferenceRetriever->shouldReceive('retrieve')
            ->with($userId, $preferenceName)
            ->twice()
            ->andReturn($preference);

        $this->userPreferenceUpdater->shouldReceive('update')
            ->with($preferenceName, $userId, $value, false)
            ->once()
            ->andReturnTrue();

        $output->shouldReceive('object_array')
            ->with([$preference], 'preference')
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
                ['filter' => $preferenceName, 'value' => $value]
            )
        );
    }
}
