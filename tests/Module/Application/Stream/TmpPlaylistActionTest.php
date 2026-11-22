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

namespace Ampache\Module\Application\Stream;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Tmp_Playlist;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class TmpPlaylistActionTest extends TestCase
{
    // a queue id is a plain request value, so serving another session's queue streams someone else's plays
    public function testAnotherSessionsQueueIsNotServed(): void
    {
        $queue = $this->queue('someone-elses-session');

        $queue->expects(self::never())
            ->method('get_items');

        $this->stream($queue);
    }

    public function testTheSessionsOwnQueueIsServed(): void
    {
        $queue = $this->queue((string) session_id());

        $queue->expects(self::once())
            ->method('get_items')
            ->willReturn([]);

        $this->stream($queue);
    }

    protected function tearDown(): void
    {
        unset($_REQUEST['tmpplaylist_id']);
    }

    private function queue(string $session): Tmp_Playlist
    {
        $queue          = $this->createMock(Tmp_Playlist::class);
        $queue->session = $session;

        return $queue;
    }

    private function stream(Tmp_Playlist $queue): void
    {
        $config = $this->createMock(ConfigContainerInterface::class);
        $config->method('get')->willReturn('');

        $_REQUEST['tmpplaylist_id'] = '5';

        $modelFactory = $this->createMock(ModelFactoryInterface::class);
        $modelFactory->method('createTmpPlaylist')
            ->with(5)
            ->willReturn($queue);

        $subject = new TmpPlaylistAction(
            $modelFactory,
            $this->createMock(LoggerInterface::class),
            $config,
        );

        self::assertNull($subject->run($this->createMock(ServerRequestInterface::class), $this->createMock(GuiGatekeeperInterface::class)));
    }
}
