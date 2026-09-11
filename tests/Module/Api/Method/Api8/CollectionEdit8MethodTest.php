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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class CollectionEdit8MethodTest extends MockeryTestCase
{
    private CollectionRepositoryInterface|MockInterface|null $collectionRepository;
    private ?CollectionEdit8Method $subject;

    public function testHandleAllowsMetadataEditFromOwner(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $collection = $this->mock(Collection::class);
        $stream     = $this->mock(StreamInterface::class);

        $this->collectionRepository->shouldReceive('findById')
            ->with(5)
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('isVisible')->with($user)->andReturn(true);
        $collection->shouldReceive('has_collaborate')->with($user)->andReturn(true);
        $collection->shouldReceive('has_access')->with($user)->andReturn(true);
        $collection->shouldReceive('getId')->andReturn(5);

        $this->collectionRepository->shouldReceive('update')
            ->with(5, null, 'public', null, null)
            ->once();

        $output->shouldReceive('collections')
            ->with(8, [5], $user, 'some-auth')
            ->once()
            ->andReturn('result');
        $response->shouldReceive('getBody')->andReturn($stream);
        $stream->shouldReceive('write')->with('result')->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => '5', 'type' => 'public', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                8
            )
        );
    }

    /**
     * A collaborator reordering the collection may not smuggle a metadata edit in on the same request; the
     * whole request is refused, and neither the reorder nor the metadata is ever written.
     */
    public function testHandleRefusesAMetadataEditBundledWithAReorderFromCollaborator(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $collection = $this->mock(Collection::class);

        $this->collectionRepository->shouldReceive('findById')
            ->with(5)
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('isVisible')->with($user)->andReturn(true);
        $collection->shouldReceive('has_collaborate')->with($user)->andReturn(true);
        $collection->shouldReceive('has_access')->with($user)->andReturn(false);

        // neither the reorder nor the metadata write may be reached for a bundled request like this
        $collection->shouldNotReceive('set_by_track_number');
        $collection->shouldNotReceive('regenerate_track_numbers');

        $this->collectionRepository->shouldNotReceive('update');

        $this->expectException(AccessFailedException::class);
        $this->expectExceptionMessage(sprintf('Require: %s', 100));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'filter' => '5',
                'name' => 'New name',
                'items' => 'song:1',
                'tracks' => '1',
                'api_format' => 'json',
                'auth' => 'some-auth',
            ],
            $user,
            8
        );
    }

    /**
     * A collaborator may curate the contents but must not edit the metadata (name, visibility, collaborators);
     * asking for a metadata change with no reorder is refused, and the metadata is never written.
     */
    public function testHandleRefusesMetadataEditFromCollaborator(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $collection = $this->mock(Collection::class);

        $this->collectionRepository->shouldReceive('findById')
            ->with(5)
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('isVisible')->with($user)->andReturn(true);
        $collection->shouldReceive('has_collaborate')->with($user)->andReturn(true);
        $collection->shouldReceive('has_access')->with($user)->andReturn(false);

        // the metadata write must never be reached for a collaborator, even with no reorder requested
        $this->collectionRepository->shouldNotReceive('update');

        $this->expectException(AccessFailedException::class);
        $this->expectExceptionMessage(sprintf('Require: %s', 100));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => '5', 'type' => 'public', 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            8
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->collectionRepository = $this->mock(CollectionRepositoryInterface::class);

        $this->subject = new CollectionEdit8Method(
            $this->collectionRepository
        );
    }
}
