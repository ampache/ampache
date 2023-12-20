<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Mockery\MockInterface;

class AjaxUriRetrieverTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configContainer;

    private AjaxUriRetriever $subject;

    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new AjaxUriRetriever(
            $this->configContainer
        );
    }

    public function testGetAjaxUriReturnsValue(): void
    {
        $webPath = 'some-path';

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        static::assertSame(
            sprintf(
                '%s/server/ajax.server.php',
                $webPath
            ),
            $this->subject->getAjaxUri()
        );
    }

    public function testGetAjaxServerUriReturnsValue(): void
    {
        $webPath = 'some-path';

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        static::assertSame(
            sprintf(
                '%s/server',
                $webPath
            ),
            $this->subject->getAjaxServerUri()
        );
    }
}
