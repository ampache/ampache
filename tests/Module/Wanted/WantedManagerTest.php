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

namespace Ampache\Module\Wanted;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Plugin\PluginRetrieverInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Wanted;
use Ampache\Repository\WantedRepositoryInterface;
use MusicBrainz\MusicBrainz;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WantedManagerTest extends TestCase
{
    private MusicBrainz&MockObject $musicBrainz;
    private PluginRetrieverInterface&MockObject $pluginRetriever;
    private WantedManager $subject;
    private WantedRepositoryInterface&MockObject $wantedRepository;

    public function testAcceptDoesNothingWithoutAccess(): void
    {
        $wanted = $this->createMock(Wanted::class);
        $user   = $this->createMock(User::class);

        $user->expects(static::once())
            ->method('has_access')
            ->with(AccessLevelEnum::MANAGER)
            ->willReturn(false);

        $this->pluginRetriever->expects(static::never())
            ->method('retrieveByType');

        $this->subject->accept($wanted, $user);
    }

    public function testAcceptMarksWantedAsAcceptedAndProcessesPlugins(): void
    {
        $wanted = $this->createMock(Wanted::class);
        $user   = $this->createMock(User::class);

        $user->expects(static::once())
            ->method('has_access')
            ->with(AccessLevelEnum::MANAGER)
            ->willReturn(true);

        $wanted->method('getMusicBrainzId')
            ->willReturn('some-mbid');

        $this->pluginRetriever->expects(static::once())
            ->method('retrieveByType')
            ->willReturnCallback(static function (): iterable {
                yield from [];
            });

        $this->subject->accept($wanted, $user);

        self::assertSame(1, $wanted->accepted);
    }

    public function testDeleteDoesNothingWhenReleaseGroupIsMissing(): void
    {
        $this->wantedRepository->expects(static::once())
            ->method('getAcceptedCount')
            ->willReturn(1);

        $this->musicBrainz->expects(static::once())
            ->method('lookup')
            ->with('release', 'some-mbid', ['release-groups'])
            ->willReturn((object) ['release-group' => null]);

        $this->wantedRepository->expects(static::never())
            ->method('deleteByMusicbrainzId');

        $this->subject->delete('some-mbid');
    }

    public function testDeleteDoesNothingWithoutAnyAcceptedItems(): void
    {
        $this->wantedRepository->expects(static::once())
            ->method('getAcceptedCount')
            ->willReturn(0);

        $this->musicBrainz->expects(static::never())
            ->method('lookup');

        $this->subject->delete('some-mbid');
    }

    public function testDeleteRemovesByReleaseGroupId(): void
    {
        $user = $this->createMock(User::class);

        $this->wantedRepository->expects(static::once())
            ->method('getAcceptedCount')
            ->willReturn(1);

        $this->musicBrainz->expects(static::once())
            ->method('lookup')
            ->with('release', 'some-mbid', ['release-groups'])
            ->willReturn((object) ['release-group' => 'some-release-group']);

        $this->wantedRepository->expects(static::once())
            ->method('deleteByMusicbrainzId')
            ->with('some-release-group', $user);

        $this->subject->delete('some-mbid', $user);
    }

    protected function setUp(): void
    {
        $this->wantedRepository = $this->createMock(WantedRepositoryInterface::class);
        $this->musicBrainz      = $this->createMock(MusicBrainz::class);
        $this->pluginRetriever  = $this->createMock(PluginRetrieverInterface::class);

        $this->subject = new WantedManager(
            $this->wantedRepository,
            $this->musicBrainz,
            $this->pluginRetriever,
        );
    }
}
