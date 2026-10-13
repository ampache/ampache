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

namespace Ampache\Module\Application\SearchData;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;

class ShowActionTest extends MockeryTestCase
{
    /**
     * `search.js`'s `SearchRow.addGroup()` builds its operator `<select>` and "add rule" link from these
     * globals -- if `run()` stops emitting one, a reconstructed group silently loses its label or its link
     */
    public function testRunEmitsTheGroupUiGlobalsAlongsideTheExistingOnes(): void
    {
        // 'label' keeps _get_types_*() to plain text/numeric rules, with no Playlist/Access lookup to mock
        $requestParser = $this->mock(RequestParserInterface::class);
        $requestParser->shouldReceive('getFromRequest')->with('type')->andReturn('label');

        $modelFactory = $this->mock(ModelFactoryInterface::class);
        $modelFactory->shouldReceive('createSearch')->andReturn(new Search(0, 'label'));

        $subject = new ShowAction(
            $requestParser,
            new Psr17Factory(),
            new Psr17Factory(),
            $modelFactory,
        );

        $body = (string) $subject->run(
            $this->mock(ServerRequestInterface::class),
            $this->mock(GuiGatekeeperInterface::class)
        )->getBody();

        $this->assertStringContainsString('var types = ', $body);
        $this->assertStringContainsString('removeIcon = ', $body);
        $this->assertStringContainsString('addRuleIcon = ', $body);
        $this->assertStringContainsString('matchLabel = ', $body);
        $this->assertStringContainsString('matchAllLabel = ', $body);
        $this->assertStringContainsString('matchAnyLabel = ', $body);
    }
}
