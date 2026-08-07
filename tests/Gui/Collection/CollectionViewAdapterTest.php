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

namespace Ampache\Gui\Collection;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Collection\DeleteCollectionAction;
use Ampache\Module\Application\Collection\SetTrackNumbersAction;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;

class CollectionViewAdapterTest extends MockeryTestCase
{
    private BrowseFactoryInterface|MockInterface|null $browseFactory;
    private Collection|MockInterface|null $collection;
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private User|MockInterface|null $user;

    public function testCanDeletePassesTheViewingUser(): void
    {
        $this->collection->shouldReceive('has_access')
            ->with($this->user)
            ->once()
            ->andReturnFalse();

        $this->assertFalse($this->createSubject()->canDelete());
    }

    public function testCanDirectPlayReturnsFalseWhenDisabled(): void
    {
        $this->collection->shouldReceive('get_medias')
            ->once()
            ->andReturn([$this->createItem(1, 1)]);
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DIRECTPLAY)
            ->once()
            ->andReturnFalse();

        $this->assertFalse($this->createSubject()->canDirectPlay());
    }

    public function testCanEditPassesTheViewingUser(): void
    {
        $this->collection->shouldReceive('has_collaborate')
            ->with($this->user)
            ->once()
            ->andReturnTrue();

        $this->assertTrue($this->createSubject()->canEdit());
    }

    /**
     * A pinned collection is rendered through its own type's browse, which has no order of its own to save.
     */
    public function testCanReorderRequiresBothEditRightsAndAMixedCollection(): void
    {
        $this->collection->object_type = null;
        $this->collection->shouldReceive('has_collaborate')
            ->with($this->user)
            ->andReturnTrue();

        $this->assertTrue($this->createSubject()->canReorder());

        $this->collection->object_type = 'song';

        $this->assertFalse($this->createSubject()->canReorder());
    }

    public function testCanReorderReturnsFalseWithoutEditRights(): void
    {
        $this->collection->object_type = null;
        $this->collection->shouldReceive('has_collaborate')
            ->with($this->user)
            ->once()
            ->andReturnFalse();

        $this->assertFalse($this->createSubject()->canReorder());
    }

    public function testCreateBrowseReturnsAStaticUnfilteredBrowse(): void
    {
        $browse = $this->mock(Browse::class);

        $this->browseFactory->shouldReceive('create')
            ->once()
            ->andReturn($browse);
        $browse->shouldReceive('set_use_filters')
            ->with(false)
            ->once();
        $browse->shouldReceive('set_static_content')
            ->with(true)
            ->once();

        $this->assertSame($browse, $this->createSubject()->createBrowse());
    }

    public function testGetCollectionReturnsTheModel(): void
    {
        $this->assertSame($this->collection, $this->createSubject()->getCollection());
    }

    public function testGetDeletionUrlReturnsValue(): void
    {
        $webPath = 'some-web-path';

        $this->configContainer->shouldReceive('getWebPath')
            ->once()
            ->andReturn($webPath);
        $this->collection->shouldReceive('getId')
            ->once()
            ->andReturn(42);

        $this->assertSame(
            sprintf(
                '%s/collection.php?action=%s&amp;collection=%d',
                $webPath,
                DeleteCollectionAction::REQUEST_KEY,
                42
            ),
            $this->createSubject()->getDeletionUrl()
        );
    }

    public function testGetIdReturnsValue(): void
    {
        $this->collection->shouldReceive('getId')
            ->once()
            ->andReturn(666);

        $this->assertSame(666, $this->createSubject()->getId());
    }

    public function testGetMemberIdsReturnsObjectIdsInOrder(): void
    {
        $items = [$this->createItem(11, 1), $this->createItem(22, 2)];

        $this->assertSame([11, 22], $this->createSubject($items)->getMemberIds());
    }

    public function testGetNameReturnsEmptyStringIfUnset(): void
    {
        $this->collection->shouldReceive('get_fullname')
            ->once()
            ->andReturnNull();

        $this->assertSame('', $this->createSubject()->getName());
    }

    public function testGetNameReturnsTheUnescapedName(): void
    {
        $this->collection->shouldReceive('get_fullname')
            ->once()
            ->andReturn('<b>Some & Name</b>');

        $this->assertSame('<b>Some & Name</b>', $this->createSubject()->getName());
    }

    public function testGetObjectIdsReturnsTheCuratedList(): void
    {
        $items = [$this->createItem(11, 1), $this->createItem(22, 2)];

        $this->assertSame($items, $this->createSubject($items)->getObjectIds());
    }

    public function testGetOwnerReturnsEmptyStringIfUnset(): void
    {
        $this->collection->username = null;

        $this->assertSame('', $this->createSubject()->getOwner());
    }

    public function testGetOwnerReturnsValue(): void
    {
        $this->collection->username = 'some-user';

        $this->assertSame('some-user', $this->createSubject()->getOwner());
    }

    /**
     * A collection stores `genre`, which normalises to `tag` -- the tag cloud browse, which cannot take ids.
     */
    public function testGetPinnedBrowseTypeMapsGenreOntoTheGenreBrowse(): void
    {
        $this->collection->object_type = 'genre';

        $this->assertSame('genre', $this->createSubject()->getPinnedBrowseType());
    }

    public function testGetPinnedBrowseTypeReturnsNullForAMixedCollection(): void
    {
        $this->collection->object_type = null;

        $this->assertNull($this->createSubject()->getPinnedBrowseType());
    }

    public function testGetPinnedBrowseTypeReturnsNullForATypeNoBrowseServes(): void
    {
        $this->collection->object_type = 'not-a-browse';

        $this->assertNull($this->createSubject()->getPinnedBrowseType());
    }

    public function testGetPinnedBrowseTypeReturnsTheBrowseName(): void
    {
        $this->collection->object_type = 'album';

        $this->assertSame('album', $this->createSubject()->getPinnedBrowseType());
    }

    public function testGetTrackNumbersUrlReturnsValue(): void
    {
        $webPath = 'some-web-path';

        $this->configContainer->shouldReceive('getWebPath')
            ->once()
            ->andReturn($webPath);
        $this->collection->shouldReceive('getId')
            ->once()
            ->andReturn(42);

        $this->assertSame(
            sprintf(
                '%s/collection.php?action=%s&collection=%d',
                $webPath,
                SetTrackNumbersAction::REQUEST_KEY,
                42
            ),
            $this->createSubject()->getTrackNumbersUrl()
        );
    }

    public function testGetTypeLabelReturnsMixedForAnUnpinnedCollection(): void
    {
        $this->collection->object_type = null;

        $this->assertSame('Mixed', $this->createSubject()->getTypeLabel());
    }

    public function testGetTypeLabelReturnsThePinnedType(): void
    {
        $this->collection->object_type = 'album';

        $this->assertSame('album', $this->createSubject()->getTypeLabel());
    }

    public function testIsEmptyReturnsValue(): void
    {
        $this->assertTrue($this->createSubject()->isEmpty());
        $this->assertFalse($this->createSubject([$this->createItem(1, 1)])->isEmpty());
    }

    /**
     * An empty string is what an unpinned collection stores, so it must read as mixed too.
     */
    public function testIsMixedTreatsNullAndEmptyStringAsMixed(): void
    {
        $this->collection->object_type = null;
        $this->assertTrue($this->createSubject()->isMixed());

        $this->collection->object_type = '';
        $this->assertTrue($this->createSubject()->isMixed());

        $this->collection->object_type = 'song';
        $this->assertFalse($this->createSubject()->isMixed());
    }

    public function testIsPlayableIsResolvedOnceForRepeatedCalls(): void
    {
        $this->collection->shouldReceive('get_medias')
            ->once()
            ->andReturn([$this->createItem(1, 1)]);

        $subject = $this->createSubject();

        $this->assertTrue($subject->isPlayable());
        $this->assertTrue($subject->isPlayable());
    }

    public function testIsPlayableReturnsFalseWhenNothingExpands(): void
    {
        $this->collection->shouldReceive('get_medias')
            ->once()
            ->andReturn([]);

        $this->assertFalse($this->createSubject()->isPlayable());
    }

    public function testIsRatingsEnabledReturnsFalseWhenDisabled(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RATINGS)
            ->once()
            ->andReturnFalse();

        $this->assertFalse($this->createSubject()->isRatingsEnabled());
    }

    public function testPlaybackActionsAreHiddenWhenNothingExpands(): void
    {
        $this->collection->shouldReceive('get_medias')
            ->once()
            ->andReturn([]);

        $subject = $this->createSubject();

        $this->assertFalse($subject->canDirectPlay());
        $this->assertFalse($subject->canPlayNext());
        $this->assertFalse($subject->canPlayAppend());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->browseFactory   = $this->mock(BrowseFactoryInterface::class);
        $this->collection      = $this->mock(Collection::class);
        $this->user            = $this->mock(User::class);
    }

    /**
     * @return array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}
     */
    private function createItem(int $objectId, int $track): array
    {
        return [
            'object_type' => LibraryItemEnum::SONG,
            'object_id' => $objectId,
            'track_id' => $track * 10,
            'track' => $track,
            'time' => 0,
        ];
    }

    /**
     * @param array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}> $objectIds
     */
    private function createSubject(array $objectIds = []): CollectionViewAdapter
    {
        return new CollectionViewAdapter(
            $this->configContainer,
            $this->browseFactory,
            $this->collection,
            $this->user,
            $objectIds
        );
    }
}
