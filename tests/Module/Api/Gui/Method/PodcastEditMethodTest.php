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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class PodcastEditMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    /** @var MockInterface|PodcastRepositoryInterface */
    private MockInterface $podcastRepository;

    private PodcastEditMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory     = $this->mock(StreamFactoryInterface::class);
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->ui                = $this->mock(UiInterface::class);
        $this->podcastRepository = $this->mock(PodcastRepositoryInterface::class);

        $this->subject = new PodcastEditMethod(
            $this->streamFactory,
            $this->configContainer,
            $this->ui,
            $this->podcastRepository
        );
    }

    public function testHandleThrowsExceptionIfPodcastIsDisabled(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: podcast');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowExceptionIfAccessIsDenied(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 50');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowExceptionIfFilterParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: filter');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowExceptionIfPodcastWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $podcast    = $this->mock(PodcastInterface::class);

        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->podcastRepository->shouldReceive('findById')
            ->with($objectId)
            ->once()
            ->andReturnNull();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleThrowExceptionIfUpdateFails(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $podcast    = $this->mock(PodcastInterface::class);

        $objectId    = 666;
        $feed        = 'some-feed';
        $title       = 'some-title';
        $website     = 'some-website';
        $description = 'some-descripion';
        $generator   = 'some-generator';
        $copyright   = 'some-copyright';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %d', $objectId));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->podcastRepository->shouldReceive('findById')
            ->with($objectId)
            ->once()
            ->andReturn($podcast);

        $podcast->shouldReceive('update')
            ->with([
                'feed' => $feed,
                'title' => $title,
                'website' => $website,
                'description' => $description,
                'generator' => $generator,
                'copyright' => $copyright
            ])
            ->once()
            ->andReturnFalse();
        $podcast->shouldReceive('getFeed')
            ->withNoArgs()
            ->once()
            ->andReturn($feed);
        $podcast->shouldReceive('getTitle')
            ->withNoArgs()
            ->once()
            ->andReturn($title);
        $podcast->shouldReceive('getWebsite')
            ->withNoArgs()
            ->once()
            ->andReturn($website);
        $podcast->shouldReceive('getDescription')
            ->withNoArgs()
            ->once()
            ->andReturn($description);
        $podcast->shouldReceive('getGenerator')
            ->withNoArgs()
            ->once()
            ->andReturn($generator);
        $podcast->shouldReceive('getCopyright')
            ->withNoArgs()
            ->once()
            ->andReturn($copyright);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleUpdates(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $podcast    = $this->mock(PodcastInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId    = 666;
        $feed        = 'http://ampache.org';
        $title       = 'some-title';
        $website     = 'http://ampache.org';
        $description = 'some-descripion';
        $generator   = 'some-generator';
        $copyright   = 'some-copyright';
        $result      = 'some-result';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->podcastRepository->shouldReceive('findById')
            ->with($objectId)
            ->once()
            ->andReturn($podcast);

        $podcast->shouldReceive('update')
            ->with([
                'feed' => $feed,
                'title' => $title,
                'website' => $website,
                'description' => $description,
                'generator' => $generator,
                'copyright' => $copyright
            ])
            ->once()
            ->andReturnTrue();

        $output->shouldReceive('success')
            ->with(sprintf('podcast %d updated', $objectId))
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

        $this->ui->shouldReceive('scrubIn')
            ->with($description)
            ->once()
            ->andReturn($description);
        $this->ui->shouldReceive('scrubIn')
            ->with($title)
            ->once()
            ->andReturn($title);
        $this->ui->shouldReceive('scrubIn')
            ->with($website)
            ->once()
            ->andReturn($website);
        $this->ui->shouldReceive('scrubIn')
            ->with($generator)
            ->once()
            ->andReturn($generator);
        $this->ui->shouldReceive('scrubIn')
            ->with($copyright)
            ->once()
            ->andReturn($copyright);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'filter' => (string) $objectId,
                    'feed' => $feed,
                    'title' => $title,
                    'website' => $website,
                    'description' => $description,
                    'generator' => $generator,
                    'copyright' => $copyright
                ]
            )
        );
    }
}
