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

namespace Ampache\Gui\Playlist;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\Playlist;

final readonly class NewPlaylistDialogAdapter implements NewPlaylistDialogAdapterInterface
{
    /**
     * Types a playlist can actually take.
     * @var list<string>
     */
    private const array PLAYLIST_TYPES = [
        'album',
        'album_disk',
        'artist',
        'label',
        'live_stream',
        'playlist',
        'podcast',
        'podcast_episode',
        'song',
        'video',
    ];

    public function __construct(
        private PlaylistLoaderInterface $playlistLoader,
        private AjaxUriRetrieverInterface $ajaxUriRetriever,
        private CollectionRepositoryInterface $collectionRepository,
        private GuiGatekeeperInterface $gatekeeper,
        private string $object_type,
        private string $object_ids,
        private string $object_groups = '',
    ) {}

    /**
     * Returns the ajax api base uri
     */
    public function getAjaxUri(): string
    {
        return $this->ajaxUriRetriever->getAjaxUri();
    }

    public function getCollectionHeading(): string
    {
        return T_('Collections');
    }

    /**
     * @return list<Collection>
     */
    public function getCollections(): array
    {
        if (!$this->getCollectionsEnabled()) {
            return [];
        }

        $user = $this->gatekeeper->getUser();
        if ($user === null) {
            return [];
        }

        $accepted = $this->acceptedTypes();

        $collections = [];
        foreach ($this->collectionRepository->getByUser($user) as $collectionId) {
            $collection = new Collection($collectionId);
            // A pinned collection only appears when it accepts every type being added, so picking it cannot
            // half-work; a mixed one accepts anything valid.
            if ($collection->isNew() || !$collection->has_collaborate($user)) {
                continue;
            }

            foreach ($accepted as $objectType) {
                if (!$collection->acceptsType($objectType)) {
                    continue 2;
                }
            }

            $collections[] = $collection;
        }

        return $collections;
    }

    /**
     * A collection can hold more types than a playlist can, but not every type: what cannot be curated is not
     * offered, so the dialog never shows a destination that would refuse the item.
     */
    public function getCollectionsEnabled(): bool
    {
        return (bool) AmpConfig::get('show_collection')
            && $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            && $this->acceptedTypes() !== [];
    }

    public function getNewCollectionTitle(): string
    {
        return T_('Collection Name');
    }

    public function getNewPlaylistTitle(): string
    {
        return T_('Playlist Name');
    }

    /**
     * A multi-select spanning types, as `type:id,id;type:id,id`. Empty for a single object.
     */
    public function getObjectGroups(): string
    {
        return $this->object_groups;
    }

    public function getObjectIds(): string
    {
        return $this->object_ids;
    }

    public function getObjectType(): string
    {
        return $this->object_type;
    }

    public function getPlaylistHeading(): string
    {
        return T_('Playlists');
    }

    /**
     * Returns a list containing all playlists of the current user
     *
     * @return Playlist[]
     */
    public function getPlaylists(): array
    {
        return $this->playlistLoader->loadByUserId(
            $this->gatekeeper->getUserId()
        );
    }

    /**
     * Whether a playlist could hold what is being added
     *
     * A genre only belongs in a collection, so the dialog drops the playlist half rather than offering a
     * destination that would silently add nothing.
     */
    public function getPlaylistsEnabled(): bool
    {
        foreach ($this->requestedTypes() as $objectType) {
            if (!in_array($objectType, self::PLAYLIST_TYPES, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The requested types, or nothing at all when even one of them cannot be curated
     *
     * @return list<string>
     */
    private function acceptedTypes(): array
    {
        $types = $this->requestedTypes();
        foreach ($types as $objectType) {
            if (!Collection::isValidType($objectType)) {
                return [];
            }
        }

        return $types;
    }

    /**
     * The types this dialog is being asked to add
     *
     * @return list<string>
     */
    private function requestedTypes(): array
    {
        $types = [];
        if ($this->object_groups !== '') {
            // `type:id,id;type:id,id` from a multi-select spanning types
            foreach (explode(';', $this->object_groups) as $group) {
                if ($group !== '') {
                    $types[] = explode(':', $group)[0];
                }
            }
        } else {
            $types[] = $this->object_type;
        }

        // The interface names a genre after its table, so it arrives as `tag` and has to be spelled the way a collection stores it before anything is checked against `VALID_TYPES`
        $types = array_map(Collection::denormalizeType(...), $types);

        // A smartlist is never stored as itself; take the songs it resolves to so it's offered wherever a song can go
        $types = array_map(
            static fn(string $objectType): string => ($objectType === 'search')
                ? 'song'
                : $objectType,
            $types
        );

        return array_values(array_unique($types));
    }
}
