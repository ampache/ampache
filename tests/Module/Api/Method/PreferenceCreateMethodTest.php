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

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\PreferenceRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class PreferenceCreateMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var PreferenceRepositoryInterface|MockInterface|null */
    private MockInterface $preferenceRepository;

    private PreferenceCreateMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory        = $this->mock(StreamFactoryInterface::class);
        $this->preferenceRepository = $this->mock(PreferenceRepositoryInterface::class);

        $this->subject = new PreferenceCreateMethod(
            $this->streamFactory,
            $this->preferenceRepository
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
            [[], 'filter'],
            [['filter' => 1], 'type'],
            [['filter' => 1, 'type' => 1], 'default'],
            [['filter' => 1, 'type' => 1, 'default' => 1], 'category'],
        ];
    }

    public function testHandleThrowsExceptionIfAccessIsDenied(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $type     = 'special';
        $filter   = 'some-name';
        $default  = 'bool';
        $category = 'some-category';

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
            [
                'type' => $type,
                'filter' => $filter,
                'default' => $default,
                'category' => $category
            ]
        );
    }

    public function testHandleThrowsExceptionIfPreferenceIsSystemPreference(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $type     = 'special';
        $filter   = 'some-name';
        $default  = 'bool';
        $category = 'some-category';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $filter));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->preferenceRepository->shouldReceive('get')
            ->with($filter, -1)
            ->once()
            ->andReturn([]);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => $type,
                'filter' => $filter,
                'default' => $default,
                'category' => $category
            ]
        );
    }

    public function testHandleThrowsExceptionIfTypeIsNotSupported(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $type     = 'some-type';
        $filter   = 'some-name';
        $default  = 'bool';
        $category = 'some-category';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $type));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->preferenceRepository->shouldReceive('get')
            ->with($filter, -1)
            ->once()
            ->andReturn([$filter]);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => $type,
                'filter' => $filter,
                'default' => $default,
                'category' => $category
            ]
        );
    }

    public function testHandleThrowsExceptionIfCategoryIsNotSupported(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $type     = 'special';
        $filter   = 'some-name';
        $default  = 'bool';
        $category = 'some-category';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $category));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->preferenceRepository->shouldReceive('get')
            ->with($filter, -1)
            ->once()
            ->andReturn([$filter]);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => $type,
                'filter' => $filter,
                'default' => $default,
                'category' => $category
            ]
        );
    }

    public function testHandleThrowsExceptionIfInsertFailed(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $type     = 'special';
        $filter   = 'some-name';
        $default  = 'bool';
        $category = 'interface';

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %s', $filter));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->preferenceRepository->shouldReceive('get')
            ->with($filter, -1)
            ->twice()
            ->andReturn([$filter], []);
        $this->preferenceRepository->shouldReceive('add')
            ->with(
                $filter,
                '',
                $default,
                AccessLevelEnum::LEVEL_ADMIN,
                $type,
                $category,
                ''
            )
            ->once();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => $type,
                'filter' => $filter,
                'default' => $default,
                'category' => $category
            ]
        );
    }

    public function testHandleAdds(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);
        $user       = $this->mock(User::class);

        $type     = 'special';
        $filter   = 'some-name';
        $default  = 'bool';
        $category = 'interface';
        $result   = 'some-result';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->preferenceRepository->shouldReceive('get')
            ->with($filter, -1)
            ->twice()
            ->andReturn([$filter]);
        $this->preferenceRepository->shouldReceive('add')
            ->with(
                $filter,
                '',
                $default,
                AccessLevelEnum::LEVEL_ADMIN,
                $type,
                $category,
                ''
            )
            ->once();

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('fixPreferences')
            ->withNoArgs()
            ->once();

        $output->shouldReceive('object_array')
            ->with([$filter], 'preference')
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
                    'type' => $type,
                    'filter' => $filter,
                    'default' => $default,
                    'category' => $category
                ]
            )
        );
    }
}
