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

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Repository\CollectionRepositoryInterface;

/**
 * A hand-curated list of objects of any type
 *
 * Unlike a playlist its members need not be playable, and unlike a search it is curated rather than computed.
 * A nullable `object_type` means mixed, a set value pins it to one type.
 */
class Collection extends playlist_object
{
    /**
     * Object types a collection may hold; each is loadable, is a `container_item` and has an output builder.
     *
     * @var list<string>
     */
    public const array VALID_TYPES = [
        'album',
        'album_disk',
        'artist',
        'folder',
        'genre',
        'label',
        'live_stream',
        'playlist',
        'podcast',
        'podcast_episode',
        'song',
        'video',
    ];

    protected const DB_TABLENAME = 'collection';

    /**
     * The type this collection is pinned to, or null when it holds a mixed bag.
     */
    public ?string $object_type = null;

    public function __construct(?int $collectionId = 0)
    {
        if (!$collectionId) {
            return;
        }

        $info = $this->get_info($collectionId, static::DB_TABLENAME);
        if ($info === []) {
            return;
        }

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        $this->id = (int) $collectionId;
    }

    /**
     * The inverse: the spelling a collection stores and `VALID_TYPES` lists.
     */
    public static function denormalizeType(string $objectType): string
    {
        return ($objectType === 'tag') ? 'genre' : $objectType;
    }

    public static function isValidType(string $objectType): bool
    {
        return in_array($objectType, self::VALID_TYPES, true);
    }

    /**
     * Normalise the API spelling of a type onto the one used for loading and storage (`genre` becomes `tag`).
     */
    public static function normalizeType(string $objectType): string
    {
        return ($objectType === 'genre') ? 'tag' : $objectType;
    }

    /**
     * Whether the server still serves this type at all
     *
     * A disabled feature drops its members from the list instead of erroring; they return when it is re-enabled.
     */
    private static function isEnabledType(string $objectType): bool
    {
        return match ($objectType) {
            'video' => (bool) AmpConfig::get('allow_video'),
            'podcast', 'podcast_episode' => (bool) AmpConfig::get('podcast'),
            'live_stream' => (bool) AmpConfig::get('live_stream'),
            'label' => (bool) AmpConfig::get('label'),
            default => true,
        };
    }

    /**
     * Whether this collection accepts a member of the given type: a mixed one takes anything valid, a pinned one
     * takes only its own type.
     */
    public function acceptsType(string $objectType): bool
    {
        if (!self::isValidType($objectType)) {
            return false;
        }

        return ($this->object_type === null || $this->object_type === '' || $this->object_type === $objectType);
    }

    /**
     * Append one object to the end of the collection.
     *
     * Duplicates follow the user's `unique_playlist` preference rather than a rule of their own, so a
     * collection behaves the way that user's playlists already do.
     *
     * @return bool false when the preference refused a duplicate
     */
    public function add_item(int $objectId, string $objectType): bool
    {
        return $this->getCollectionRepository()->addItem(
            $this->getId(),
            $objectId,
            $objectType,
            (bool) AmpConfig::get('unique_playlist', false)
        );
    }

    /**
     * The member type that stops this collection being pinned to $objectType, or null when the change is allowed.
     */
    public function conflictingType(string $objectType): ?string
    {
        foreach ($this->getCollectionRepository()->getItemTypes($this->getId()) as $type) {
            if ($type !== $objectType) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Remove one member by its `collection_map` row, the address a row in the interface carries
     *
     * The caller renumbers, so a multi-select can drop several members and pay for one renumber.
     */
    public function delete_track(int $mapId): bool
    {
        $this->getCollectionRepository()->removeItemById($this->getId(), $mapId);

        return true;
    }

    /**
     * Remove the member holding one position, then close the gap it left
     */
    public function delete_track_number(int $track): bool
    {
        $repository = $this->getCollectionRepository();
        $repository->removeItemByTrack($this->getId(), $track);
        $repository->regenerateTrackNumbers($this->getId());

        return true;
    }

    /**
     * Art is stored against `collection`; the parent would resolve the type to `playlist` and show that row's art.
     */
    public function display_art(array $size, bool $force = false, bool $link = true): void
    {
        if (AmpConfig::get('playlist_art') || $force) {
            Art::display(
                'collection',
                $this->id,
                (string) $this->get_fullname(),
                $size,
                ($link) ? $this->get_link() : null
            );
        }
    }

    public function get_item_count(): int
    {
        return $this->getCollectionRepository()->getItemCount($this->getId());
    }

    /**
     * The collection's members, as curated -- one row of `collection_map` each, containers included.
     *
     * `time` is 0 on every row; for the playable expansion see self::get_medias().
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int, track: int, track_id: int, time: int}>
     */
    public function get_items(): array
    {
        $items = [];
        foreach ($this->getCollectionRepository()->getItems($this->getId()) as $item) {
            $type = LibraryItemEnum::tryFrom(self::normalizeType($item['object_type']));
            if ($type === null || !self::isEnabledType($item['object_type'])) {
                continue;
            }

            $items[] = [
                'object_type' => $type,
                'object_id' => $item['object_id'],
                'track' => $item['track'],
                'track_id' => $item['id'],
                'time' => 0,
            ];
        }

        return $items;
    }

    /**
     * Members grouped by type, so each group can be handed to its own `*_array()` output builder
     *
     * @return array<string, list<int>>
     */
    public function get_items_by_type(): array
    {
        $grouped = [];
        foreach ($this->getCollectionRepository()->getItems($this->getId()) as $item) {
            if (!self::isEnabledType($item['object_type'])) {
                continue;
            }

            $grouped[$item['object_type']][] = $item['object_id'];
        }

        return $grouped;
    }

    public function get_link(): string
    {
        if ($this->link === null) {
            $this->link = AmpConfig::get_web_path('/client') . '/collection.php?action=show&collection=' . $this->id;
        }

        return $this->link;
    }

    /**
     * The playable expansion of the collection: what each member contains, de-duplicated.
     *
     * Anything unloadable, or holding nothing playable, is skipped rather than failing the play.
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int, track: int, track_id: int, time: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        $medias = [];
        $seen   = [];
        $track  = 0;
        foreach ($this->get_items() as $item) {
            $libitem = $this->getLibraryItemLoader()->load($item['object_type'], $item['object_id']);
            if (!$libitem instanceof container_item) {
                continue;
            }

            foreach ($libitem->get_medias($filter_type) as $media) {
                // A song reachable through two members (its album and itself) must still queue once.
                $key = $media['object_type']->value . '-' . $media['object_id'];
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;

                // The playlist contract this overrides carries a queue position; `time` stays 0 to avoid a load
                $medias[] = [
                    'object_type' => $media['object_type'],
                    'object_id' => $media['object_id'],
                    'track' => ++$track,
                    'track_id' => $media['object_id'],
                    'time' => 0,
                ];
            }
        }

        return $medias;
    }

    /**
     * The members in curated order, keeping the API spelling of each type
     *
     * `get_items()` resolves the type to a `LibraryItemEnum`, which turns `genre` into `tag`; the API output
     * needs the spelling the member was stored under, and the position it holds.
     *
     * @return list<array{object_type: string, object_id: int, track: int, track_id: int}>
     */
    public function get_ordered_items(): array
    {
        $items = [];
        foreach ($this->getCollectionRepository()->getItems($this->getId()) as $item) {
            if (!self::isValidType($item['object_type']) || !self::isEnabledType($item['object_type'])) {
                continue;
            }

            $items[] = [
                'object_type' => $item['object_type'],
                'object_id' => $item['object_id'],
                'track' => $item['track'],
                'track_id' => $item['id'],
            ];
        }

        return $items;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::COLLECTION;
    }

    public function has_art(): bool
    {
        return Art::has_db($this->id, 'collection');
    }

    public function has_item(int $objectId, string $objectType): bool
    {
        return $this->getCollectionRepository()->hasItem($this->getId(), $objectId, $objectType);
    }

    /**
     * Whether the user may see this collection at all; a collaborator counts, they are invited to curate it.
     */
    public function isVisible(?User $user = null): bool
    {
        return ($this->type === 'public' || $this->has_collaborate($user));
    }

    /**
     * Renumber the members from 1 so the positions stay dense
     */
    public function regenerate_track_numbers(): void
    {
        $this->getCollectionRepository()->regenerateTrackNumbers($this->getId());
    }

    /**
     * Put one object at one position, dropping whatever held that position before.
     *
     * This is how a partial reorder is expressed: the caller sends only the positions it wants to change.
     */
    public function set_by_track_number(int $objectId, string $objectType, int $track): bool
    {
        if (!$this->acceptsType($objectType)) {
            return false;
        }

        if (
            AmpConfig::get('unique_playlist', false)
            && $this->has_item($objectId, $objectType)
        ) {
            return false;
        }

        $this->getCollectionRepository()->replaceTrackAtNumber($this->getId(), $objectId, $objectType, $track);

        return true;
    }

    /**
     * Save an edit from the web dialog.
     *
     * The parent would write these fields into the `playlist` row sharing this id. Form values arrive as strings.
     *
     * @param array<string, mixed>|null $data
     */
    public function update(?array $data = null): int
    {
        if ($this->isNew() || $data === null) {
            return 0;
        }

        if (!$this->canWrite()) {
            return $this->id;
        }

        $name = (isset($data['name']) && (string) $data['name'] !== '')
            ? (string) $data['name']
            : null;

        // The dialog posts `type=collection_row` for routing, so visibility travels as `collection_type`
        $type = (isset($data['collection_type']))
            ? (((string) $data['collection_type'] === 'public') ? 'public' : 'private')
            : null;

        $objectType = null;
        if (array_key_exists('object_type', $data)) {
            $candidate = (string) $data['object_type'];
            // An empty string un-pins back to mixed; a real type must also survive the contents already curated
            if ($candidate === '') {
                $objectType = '';
            } elseif (self::isValidType($candidate) && $this->conflictingType($candidate) === null) {
                $objectType = $candidate;
            } else {
                debug_event(self::class, 'Refused to pin collection ' . $this->id . ' to ' . $candidate, 3);
            }
        }

        // A multi-select with nothing selected posts no key, so an absent `collaborate` means the list was cleared
        $list        = (isset($data['collaborate']) && is_array($data['collaborate'])) ? $data['collaborate'] : [];
        $collaborate = implode(',', array_map(static fn($userId): string => (string) (int) $userId, $list));

        $this->getCollectionRepository()->update($this->id, $name, $type, $objectType, $collaborate);

        // `memory_cache` is on by default, so the writing object must be updated or it serves the pre-edit row
        if ($name !== null) {
            $this->name = $name;
        }

        if ($type !== null) {
            $this->type = $type;
        }

        if ($objectType !== null) {
            $this->object_type = ($objectType === '') ? null : $objectType;
        }

        $this->collaborate = $collaborate;

        return $this->id;
    }

    /**
     * Store the position of one member without renumbering, for a caller writing a whole new order
     */
    public function update_track_number(int $mapId, int $track): void
    {
        $this->getCollectionRepository()->setTrackNumber($mapId, $track);
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    protected function get_art_items(): array
    {
        return $this->get_items();
    }

    /**
     * @deprecated inject dependency
     */
    private function getCollectionRepository(): CollectionRepositoryInterface
    {
        global $dic;

        return $dic->get(CollectionRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private function getLibraryItemLoader(): LibraryItemLoaderInterface
    {
        global $dic;

        return $dic->get(LibraryItemLoaderInterface::class);
    }
}
