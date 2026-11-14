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

namespace Ampache\Module\Database\Query;

use Ampache\Gui\Browse\ListRenderer\BrowseListRendererLocatorInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Override;

class BrowseUpdateFromSessionTest extends MockeryTestCase
{
    /** @var array<string, mixed> */
    private array $savedSession = [];

    /** Going back to an infinite scroll list hid everything above the last batch (#2256) */
    public function testAnInfiniteScrollListReopensAtTheTop(): void
    {
        $browse = $this->subject(false);

        $browse->update_browse_from_session();

        self::assertSame(0, $browse->get_start());
    }

    public function testAPagedListReopensOnTheLastPage(): void
    {
        $browse = $this->subject(true);

        $browse->update_browse_from_session();

        self::assertSame(50, $browse->get_start());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->savedSession = $_SESSION ?? [];

        $_SESSION = ['browse_current_song' => ['start' => 50]];
    }

    #[Override]
    protected function tearDown(): void
    {
        $_SESSION = $this->savedSession;

        parent::tearDown();
    }

    private function subject(bool $usePages): Browse
    {
        $browse = new Browse(
            $this->createMock(AjaxUriRetrieverInterface::class),
            $this->createMock(BrowseListRendererLocatorInterface::class),
            0,
            false
        );
        $browse->set_type('song');
        $browse->set_simple_browse(true);
        $browse->set_use_pages($usePages, false);
        $browse->set_offset(25);
        $browse->set_total(100);

        return $browse;
    }
}
