<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\Exchange\PodcastExporterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class ExportPodcastsActionTest extends TestCase
{
    use ConsecutiveParams;

    private ConfigContainerInterface&MockObject $configContainer;

    private PodcastExporterInterface&MockObject $podcastExporter;

    private ResponseFactoryInterface&MockObject $responseFactory;

    private ExportPodcastsAction $subject;

    private ServerRequestInterface&MockObject $request;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->podcastExporter = $this->createMock(PodcastExporterInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);

        $this->subject = new ExportPodcastsAction(
            $this->configContainer,
            $this->podcastExporter,
            $this->responseFactory
        );

        $this->request    = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
    }

    public function testRunReturnsNullIfPodcastsAreDisabled(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(false);

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunExportsAllSubscriptions(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream   = $this->createMock(StreamInterface::class);

        $result      = 'some-result';
        $contentType = 'some-content-type';

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(true);

        $this->responseFactory->expects(static::once())
            ->method('createResponse')
            ->willReturn($response);

        $response->expects(static::exactly(2))
            ->method('withHeader')
            ->with(
                ...self::withConsecutive(
                    ['Content-Disposition', static::stringContains('ampache')],
                    ['Content-Type', $contentType]
                )
            )
            ->willReturnSelf();
        $response->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $stream->expects(static::once())
            ->method('write')
            ->with($result);

        $this->podcastExporter->expects(static::once())
            ->method('export')
            ->willReturn($result);
        $this->podcastExporter->expects(static::once())
            ->method('getContentType')
            ->willReturn($contentType);

        static::assertSame(
            $response,
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }
}
