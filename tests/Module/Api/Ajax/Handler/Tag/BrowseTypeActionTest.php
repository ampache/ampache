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

namespace Ampache\Module\Api\Ajax\Handler\Tag;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BrowseTypeActionTest extends MockeryTestCase
{
    private MockInterface $modelFactory;

    private BrowseTypeAction $subject;

    public function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new BrowseTypeAction(
            $this->modelFactory
        );
    }

    public function testHandleSetsFilter(): void
    {
        $request  = $this->mock(ServerRequestInterface::class);
        $response = $this->mock(ResponseInterface::class);
        $user     = $this->mock(User::class);
        $browse   = $this->mock(Browse::class);

        $browseId = 666;
        $type     = 'some-type';

        $this->modelFactory->shouldReceive('createBrowse')
            ->with($browseId)
            ->once()
            ->andReturn($browse);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'browse_id' => (string) $browseId,
                'type' => $type
            ]);

        $browse->shouldReceive('set_filter')
            ->with('object_type', $type)
            ->once();
        $browse->shouldReceive('store')
            ->withNoArgs()
            ->once();

        $this->assertSame(
            [],
            $this->subject->handle($request, $response, $user)
        );
    }
}
