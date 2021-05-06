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
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Userflag;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class FlagMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private FlagMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory   = $this->mock(StreamFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);

        $this->subject = new FlagMethod(
            $this->streamFactory,
            $this->configContainer,
            $this->modelFactory
        );
    }

    public function testHandleThrowsExceptionIfUserflagsAreDisabled(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: userflags');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_FLAGS)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function vitalDataProvider(): array
    {
        return [
            [[], 'type'],
            [['type' => 1], 'id'],
            [['type' => 1, 'id' => 1], 'flag']
        ];
    }

    /**
     * @dataProvider vitalDataProvider
     */
    public function testHandleThrowsExceptionIfVitalRequestParamsMiss(
        array $input,
        string $missingKey
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $missingKey));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_FLAGS)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            $input
        );
    }

    public function testHandleThrowsExceptionIfTypeNotSupported(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $type = 'foobar';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $type));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_FLAGS)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'id' => 666,
                'flag' => 0,
                'type' => $type
            ]
        );
    }

    public function testHandleThrowsExceptionIfItemWasNotFound(): void
    {
        $gatekeeper      = $this->mock(GatekeeperInterface::class);
        $response        = $this->mock(ResponseInterface::class);
        $output          = $this->mock(ApiOutputInterface::class);
        $database_object = $this->mock(library_item::class);

        $type     = 'song';
        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf(T_('Not Found: %d'), $objectId));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_FLAGS)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($database_object);

        $database_object->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'id' => (string) $objectId,
                'flag' => 1,
                'type' => $type
            ]
        );
    }

    public function testHandleThrowsExceptionIfFlagCouldNotBeSet(): void
    {
        $gatekeeper      = $this->mock(GatekeeperInterface::class);
        $response        = $this->mock(ResponseInterface::class);
        $output          = $this->mock(ApiOutputInterface::class);
        $database_object = $this->mock(library_item::class);
        $userflag        = $this->mock(Userflag::class);

        $type     = 'song';
        $objectId = 666;
        $userId   = 42;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('flag failed %d'), $objectId));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_FLAGS)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($database_object);
        $this->modelFactory->shouldReceive('createUserFlag')
            ->with($objectId, $type)
            ->once()
            ->andReturn($userflag);

        $database_object->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $userflag->shouldReceive('set_flag')
            ->with(true, $userId)
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'id' => (string) $objectId,
                'flag' => 1,
                'type' => $type
            ]
        );
    }

    public function testHandleSetsFlag(): void
    {
        $gatekeeper      = $this->mock(GatekeeperInterface::class);
        $response        = $this->mock(ResponseInterface::class);
        $output          = $this->mock(ApiOutputInterface::class);
        $database_object = $this->mock(library_item::class);
        $userflag        = $this->mock(Userflag::class);
        $stream          = $this->mock(StreamInterface::class);

        $type     = 'song';
        $objectId = 666;
        $userId   = 42;
        $result   = 'some-result';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_FLAGS)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($database_object);
        $this->modelFactory->shouldReceive('createUserFlag')
            ->with($objectId, $type)
            ->once()
            ->andReturn($userflag);

        $database_object->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $userflag->shouldReceive('set_flag')
            ->with(true, $userId)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $output->shouldReceive('success')
            ->with('flag ADDED to ' . $objectId)
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
                    'id' => (string) $objectId,
                    'flag' => 1,
                    'type' => $type
                ]
            )
        );
    }
}
