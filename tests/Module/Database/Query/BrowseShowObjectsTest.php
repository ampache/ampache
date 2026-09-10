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
use ReflectionProperty;
use Throwable;

class BrowseShowObjectsTest extends MockeryTestCase
{
    public function testAHandedEmptyListOverwritesWhatTheBrowseHadSaved(): void
    {
        // this is the sharp edge the options toggle cut itself on: [] is a real answer that
        // replaces the saved ids, so a caller with nothing new to say has to pass null instead
        $browse = $this->subject();
        $browse->set_type('song');
        $browse->save_objects([12, 34]);

        $this->showQuietly($browse, []);

        self::assertSame([], $this->savedList($browse));
    }

    public function testPassingNullKeepsTheSavedList(): void
    {
        $browse = $this->subject();
        $browse->set_type('song');
        $browse->save_objects([12, 34]);

        $this->showQuietly($browse, null);

        self::assertSame([12, 34], $this->savedList($browse));
    }

    /**
     * The saved list, read where it lives: get_saved() falls back to the tmp_browse table when
     * the local copy is empty, and an uncached unit test has no table to fall back to.
     */
    private function savedList(Browse $browse): array
    {
        return (array) new ReflectionProperty(Query::class, '_cache')->getValue($browse);
    }

    /**
     * The dispatch under test happens in the first lines of show_objects(); everything after
     * that is rendering, which reaches for the container a unit test does not have. The saved
     * state is written before that point, so it stays assertable either way.
     */
    private function showQuietly(Browse $browse, ?array $objectIds): void
    {
        ob_start();
        try {
            $browse->show_objects($objectIds);
        } catch (Throwable) {
            // rendering infrastructure is absent on purpose
        } finally {
            ob_end_clean();
        }
    }

    private function subject(): Browse
    {
        $retriever = $this->createMock(AjaxUriRetrieverInterface::class);
        $retriever->method('getAjaxUri')->willReturn('');

        // no renderer on purpose: the state under test is written before rendering starts,
        // and a missing renderer makes show_objects() return quietly right after
        $locator = $this->createMock(BrowseListRendererLocatorInterface::class);
        $locator->method('find')->willReturn(null);

        return new Browse($retriever, $locator, 0, false);
    }
}
