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

namespace Ampache\Gui\Partial;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Playback\LocalplayControlView;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\displayable_item;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Override;

/**
 * The play queue in the right-hand bar.
 *
 * The queue has no ceiling, so it is counted and read separately: the count comes from a `COUNT(*)` and
 * only the first hundred rows are ever hydrated for display.
 */
final class RightbarView extends AbstractView
{
    private const int DISPLAY_LIMIT = 100;

    private ?int $count = null;

    /**
     * @var list<array{link: string, trackId: int}>|null
     */
    private ?array $items = null;

    public function __construct(
        private readonly CollectionRepositoryInterface $collectionRepository,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
        private readonly PlaylistLoaderInterface $playlistLoader,
        private readonly ZipHandlerInterface $zipHandler,
        private readonly string $webPath,
    ) {}

    public function getBasketCount(): int
    {
        if ($this->count === null) {
            $user        = Core::get_global('user');
            $this->count = (!defined('NO_SONGS') && $user instanceof User && $user->playlist)
                ? $user->playlist->count_items()
                : 0;
        }

        return $this->count;
    }

    public function getBatchUrl(): string
    {
        $user = Core::get_global('user');

        return $this->webPath . '/batch.php?action=tmp_playlist&id=' . (($user instanceof User) ? $user->playlist?->id : 0);
    }

    /**
     * The collections this user may add to.
     *
     * @return list<Collection>
     */
    public function getCollections(): array
    {
        $user = Core::get_global('user');
        if (!AmpConfig::get('show_collection') || !$user instanceof User) {
            return [];
        }

        $collections = [];
        foreach ($this->collectionRepository->getByUser($user) as $collectionId) {
            $collection = new Collection($collectionId);
            if ($collection->isNew() || !$collection->has_collaborate($user)) {
                continue;
            }

            $collections[] = $collection;
        }

        return $collections;
    }

    /**
     * The queued rows this bar shows, already resolved to displayable objects.
     *
     * @return list<array{link: string, trackId: int}>
     */
    public function getItems(): array
    {
        if ($this->items !== null) {
            return $this->items;
        }

        $user = Core::get_global('user');
        $rows = (!defined('NO_SONGS') && $user instanceof User && $user->playlist)
            ? $user->playlist->get_items(self::DISPLAY_LIMIT)
            : [];

        $items = [];
        foreach ($rows as $row) {
            $object = $this->libraryItemLoader->load(
                $row['object_type'],
                $row['object_id'],
                [Broadcast::class, Live_Stream::class, Podcast_Episode::class, Song::class, Song_Preview::class, Video::class]
            );
            if ($object instanceof displayable_item) {
                $items[] = ['link' => $object->get_f_link(), 'trackId' => (int) $row['track_id']];
            }
        }

        return $this->items = $items;
    }

    public function getLocalplayControl(): string
    {
        return (new LocalplayControlView())->render();
    }

    /**
     * @return iterable<mixed>
     */
    public function getPlaylists(): iterable
    {
        $user = Core::get_global('user');

        return $this->playlistLoader->loadByUserId(($user instanceof User) ? $user->getId() : -1);
    }

    public function getPlayType(): string
    {
        return (string) AmpConfig::get('play_type');
    }

    /**
     * How many queued items the list is not showing.
     */
    public function getTruncatedCount(): int
    {
        return max(0, $this->getBasketCount() - self::DISPLAY_LIMIT);
    }

    public function mayAdd(): bool
    {
        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    public function mayBatchDownload(): bool
    {
        return Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->zipHandler->isZipable('tmp_playlist');
    }

    public function showLocalplay(): bool
    {
        return $this->getPlayType() === 'localplay';
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('partial/rightbar.phtml');
    }
}
