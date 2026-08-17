<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Application\Batch;

use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class DefaultActionTest extends MockeryTestCase
{
    private MockInterface|BrowseFactoryInterface $browseFactory;
    private MockInterface|FunctionCheckerInterface $functionChecker;
    private MockInterface|LibraryItemLoaderInterface $libraryItemLoader;
    private MockInterface|LoggerInterface $logger;
    private MockInterface|ModelFactoryInterface $modelFactory;
    private MockInterface|RequestParserInterface $requestParser;
    private MockInterface|ResponseFactoryInterface $responseFactory;
    private MockInterface|SongRepositoryInterface $songRepository;
    private DefaultAction $subject;
    private MockInterface|ZipHandlerInterface $zipHandler;

    /**
     * Every id fans out to a full item load plus its own medias, so an unbounded request-supplied
     * list is a resource-exhaustion vector; it must be refused before any of that work starts
     */
    public function testRunRefusesAnIdListOverTheLimit(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->functionChecker->shouldReceive('check')
            ->with(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD)
            ->once()
            ->andReturnTrue();

        $this->requestParser->shouldReceive('getFromRequest')
            ->with('action')
            ->once()
            ->andReturn('song');

        $this->zipHandler->shouldReceive('isZipable')
            ->with('song')
            ->once()
            ->andReturnTrue();

        $this->requestParser->shouldReceive('getFromRequest')
            ->with('id')
            ->once()
            ->andReturn(implode(',', range(1, 501)));

        $this->libraryItemLoader->shouldNotReceive('load');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($request, $gatekeeper);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->requestParser     = $this->mock(RequestParserInterface::class);
        $this->modelFactory      = $this->mock(ModelFactoryInterface::class);
        $this->browseFactory     = $this->mock(BrowseFactoryInterface::class);
        $this->logger            = $this->mock(LoggerInterface::class);
        $this->zipHandler        = $this->mock(ZipHandlerInterface::class);
        $this->functionChecker   = $this->mock(FunctionCheckerInterface::class);
        $this->songRepository    = $this->mock(SongRepositoryInterface::class);
        $this->responseFactory   = $this->mock(ResponseFactoryInterface::class);
        $this->libraryItemLoader = $this->mock(LibraryItemLoaderInterface::class);

        $this->logger->shouldReceive('warning');

        $this->subject = new DefaultAction(
            $this->requestParser,
            $this->modelFactory,
            $this->browseFactory,
            $this->logger,
            $this->zipHandler,
            $this->functionChecker,
            $this->songRepository,
            $this->responseFactory,
            $this->libraryItemLoader,
        );
    }
}
