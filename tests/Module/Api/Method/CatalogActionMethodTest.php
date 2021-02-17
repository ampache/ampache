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
use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Catalog\Loader\Exception\CatalogNotFoundException;
use Ampache\Module\Catalog\Process\CatalogProcessInterface;
use Ampache\Module\Catalog\Process\CatalogProcessTypeMapperInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class CatalogActionMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var CatalogProcessTypeMapperInterface|MockInterface|null */
    private MockInterface $catalogProcessTypeMapper;

    /** @var CatalogLoaderInterface|MockInterface|null */
    private MockInterface $catalogLoader;

    /** @var UpdateInfoRepositoryInterface|MockInterface|null */
    private MockInterface $updateInfoRepository;

    private ?CatalogActionMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory            = $this->mock(StreamFactoryInterface::class);
        $this->catalogProcessTypeMapper = $this->mock(CatalogProcessTypeMapperInterface::class);
        $this->catalogLoader            = $this->mock(CatalogLoaderInterface::class);
        $this->updateInfoRepository     = $this->mock(UpdateInfoRepositoryInterface::class);

        $this->subject = new CatalogActionMethod(
            $this->streamFactory,
            $this->catalogProcessTypeMapper,
            $this->catalogLoader,
            $this->updateInfoRepository
        );
    }

    public function testHandleThrowsExceptionIfTaskParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: task');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
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
            ['task' => 'some-task']
        );
    }

    public function testHandleThrowsExceptionIfTaskIsNotKnown(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $task = 'some-task';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $task));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->catalogProcessTypeMapper->shouldReceive('map')
            ->with($task)
            ->once()
            ->andReturnNull();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['task' => $task]
        );
    }

    public function testHandleThrowsExceptionCatalogDoesNotExist(): void
    {
        $gatekeeper  = $this->mock(GatekeeperInterface::class);
        $response    = $this->mock(ResponseInterface::class);
        $output      = $this->mock(ApiOutputInterface::class);
        $processType = $this->mock(CatalogProcessInterface::class);

        $task        = 'some-task';

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage('Not Found');

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->catalogProcessTypeMapper->shouldReceive('map')
            ->with($task)
            ->once()
            ->andReturn($processType);

        $this->catalogLoader->shouldReceive('byId')
            ->with(0)
            ->once()
            ->andThrow(new CatalogNotFoundException());

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['task' => $task]
        );
    }

    public function testHandleReturnsData(): void
    {
        $gatekeeper  = $this->mock(GatekeeperInterface::class);
        $response    = $this->mock(ResponseInterface::class);
        $output      = $this->mock(ApiOutputInterface::class);
        $processType = $this->mock(CatalogProcessInterface::class);
        $catalog     = $this->mock(Catalog::class);
        $stream      = $this->mock(StreamInterface::class);

        $task      = 'some-task';
        $catalogId = 123;
        $result    = 'some-result';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->catalogProcessTypeMapper->shouldReceive('map')
            ->with($task)
            ->once()
            ->andReturn($processType);

        $this->catalogLoader->shouldReceive('byId')
            ->with($catalogId)
            ->once()
            ->andReturn($catalog);

        $processType->shouldReceive('process')
            ->with($catalog)
            ->once();

        $this->updateInfoRepository->shouldReceive('countServer')
            ->withNoArgs()
            ->once();

        $output->shouldReceive('success')
            ->with(sprintf('successfully started: %s', $task))
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
                ['task' => $task, 'catalog' => $catalogId]
            )
        );
    }
}
