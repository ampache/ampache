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

namespace Ampache\Module\Application\Collection;

use Ampache\Gui\Collection\CollectionViewAdapterInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\Partial\PageMeta;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Collection;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ShowActionTest extends MockeryTestCase
{
    private BrowseFactoryInterface&MockInterface $browseFactory;
    private CollectionRepositoryInterface&MockInterface $collectionRepository;
    private GuiFactoryInterface&MockInterface $guiFactory;
    private LoggerInterface&MockInterface $logger;
    private ShowAction $subject;
    private UiInterface&MockInterface $ui;

    public function testRunDescribesACollectionTheViewerMaySee(): void
    {
        $collection = $this->collection(true);
        $adapter    = $this->mock(CollectionViewAdapterInterface::class);

        $collection->shouldReceive('get_fullname')->andReturn('Some list');
        $collection->shouldReceive('getId')->andReturn(7);
        $collection->shouldReceive('get_items')->andReturn([]);
        $this->guiFactory->shouldReceive('createCollectionViewAdapter')->once()->andReturn($adapter);
        $adapter->shouldReceive('render')->once()->andReturn('');

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        ob_start();
        $this->subject->run($this->request(), $this->mock(GuiGatekeeperInterface::class));
        ob_end_clean();

        self::assertStringContainsString('og:title" content="Some list"', PageMeta::render());
    }

    public function testRunSaysNothingAboutACollectionTheViewerMayNotSee(): void
    {
        $collection = $this->collection(false);

        // the tell that the guard fired: the members are never read and the head is told nothing at all
        $collection->shouldReceive('get_items')->never();
        $this->guiFactory->shouldReceive('createCollectionViewAdapter')->never();

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();
        $this->logger->shouldReceive('warning')->once();

        ob_start();
        $this->subject->run($this->request(), $this->mock(GuiGatekeeperInterface::class));
        ob_end_clean();

        self::assertSame('', PageMeta::render());
    }

    #[Override]
    protected function setUp(): void
    {
        PageMeta::render();
        unset($GLOBALS['user']);

        $this->ui                   = $this->mock(UiInterface::class);
        $this->logger               = $this->mock(LoggerInterface::class);
        $this->collectionRepository = $this->mock(CollectionRepositoryInterface::class);
        $this->browseFactory        = $this->mock(BrowseFactoryInterface::class);
        $this->guiFactory           = $this->mock(GuiFactoryInterface::class);

        $this->subject = new ShowAction(
            $this->ui,
            $this->logger,
            $this->collectionRepository,
            $this->browseFactory,
            $this->guiFactory
        );
    }

    #[Override]
    protected function tearDown(): void
    {
        PageMeta::render();
    }

    private function collection(bool $visible): Collection&MockInterface
    {
        $collection = $this->mock(Collection::class);
        $collection->shouldReceive('isVisible')->with(null)->andReturn($visible);
        $this->collectionRepository->shouldReceive('findById')->andReturn($collection);

        return $collection;
    }

    private function request(): ServerRequestInterface&MockInterface
    {
        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getQueryParams')->andReturn(['collection' => '7']);

        return $request;
    }
}
