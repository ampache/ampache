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

namespace Ampache\Module\Api\Gui\Method;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\User\Management\Exception\UserCreationFailedException;
use Ampache\Module\User\Management\UserCreatorInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class UserCreateMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var UserCreatorInterface|MockInterface|null */
    private MockInterface $userCreator;

    private UserCreateMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);
        $this->userCreator   = $this->mock(UserCreatorInterface::class);

        $this->subject = new UserCreateMethod(
            $this->streamFactory,
            $this->userCreator
        );
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
            []
        );
    }

    /**
     * @dataProvider requestParamDataProvider
     */
    public function testHandleThrowsExceptionIfRequestParamDoesNotExist(
        array $input,
        string $keyName
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $keyName));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

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
            [[], 'username'],
            [['username' => 1], 'password'],
            [['username' => 1, 'password' => 1], 'email'],
        ];
    }

    public function testHandleThrowsExceptionIfUserCreationFails(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $username = 'some-username';
        $email    = 'some-email';
        $password = 'some-password';
        $fullname = 'some-fullname';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $username));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->userCreator->shouldReceive('create')
            ->with(
                $username,
                $fullname,
                $email,
                '',
                $password,
                AccessLevelEnum::LEVEL_USER,
                '',
                '',
                false,
                true
            )
            ->once()
            ->andThrow(new UserCreationFailedException());

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'username' => $username,
                'fullname' => $fullname,
                'email' => $email,
                'password' => $password,
            ]
        );
    }

    public function testHandleCreatesUser(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $username = 'some-username';
        $email    = 'some-email';
        $password = 'some-password';
        $result   = 'some-result';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->userCreator->shouldReceive('create')
            ->with(
                $username,
                $username,
                $email,
                '',
                $password,
                AccessLevelEnum::LEVEL_USER,
                '',
                '',
                true,
                true
            )
            ->once()
            ->andReturn($this->mock(User::class));

        $output->shouldReceive('success')
            ->with(sprintf('successfully created: %s', $username))
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
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'disable' => 1
                ]
            )
        );
    }
}
