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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\User;

/**
 * Curating one collection's members from the item list, without leaving the page
 *
 * The counterpart of `PlaylistAjaxHandler::delete_track`.
 */
final readonly class CollectionAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private CollectionRepositoryInterface $collectionRepository,
    ) {}

    public function handle(User $user): void
    {
        $results = [];
        $action  = $this->requestParser->getFromRequest('action');

        switch ($action) {
            case 'append_item':
                // Unlike a playlist, a collection stores the object itself rather than the media it expands to,
                // so an album added here stays one row and keeps looking like an album.
                if ($this->requestParser->getFromRequest('collection_id') === '') {
                    if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
                        debug_event('collection.ajax', 'Error:' . $user->username . ' does not have user access, unable to create collection', 1);
                        break;
                    }

                    $name = $this->requestParser->getFromRequest('name');
                    if ($name === '') {
                        $name = $user->username . ' - ' . get_datetime(time());
                    }

                    // Private by default, matching `collection_create`; the owner can publish it from the edit dialog
                    $collectionId = $this->collectionRepository->create($name, $user);
                    if ($collectionId === null) {
                        break;
                    }

                    $collection = $this->collectionRepository->findById($collectionId);
                } else {
                    $collection = $this->collectionRepository->findById(
                        (int) $this->requestParser->getFromRequest('collection_id')
                    );
                }

                if (
                    $collection === null
                    || !$collection->has_collaborate($user)
                ) {
                    break;
                }

                // The interface sends a genre as `tag`, after its table; a collection stores it as `genre`
                $itemType   = Collection::denormalizeType($this->requestParser->getFromRequest('item_type'));
                $requestIds = $this->requestParser->getFromRequest('item_id');

                /** @var list<array{id: int, type: string}> $pending */
                $pending = [];
                if ($itemType === '' && $requestIds === '') {
                    foreach ($user->playlist?->get_items() ?? [] as $item) {
                        $pending[] = ['id' => (int) $item['object_id'], 'type' => $item['object_type']->value];
                    }
                } elseif ($itemType === 'search') {
                    // A smartlist is not a storable type, so it adds the songs it resolves to instead
                    foreach (explode(',', $requestIds) as $itemId) {
                        $smartlist = new Smartlist((int) $itemId);
                        if ($smartlist->isNew()) {
                            continue;
                        }

                        foreach ($smartlist->get_medias('song') as $media) {
                            $pending[] = ['id' => (int) $media['object_id'], 'type' => 'song'];
                        }
                    }
                } else {
                    foreach (explode(',', $requestIds) as $itemId) {
                        $pending[] = ['id' => (int) $itemId, 'type' => $itemType];
                    }
                }

                $added = 0;
                foreach ($pending as $item) {
                    // A pinned collection refuses anything but its own type, and an id that is not in its own
                    // table would leave the collection holding a dangling member
                    if (
                        $item['id'] < 1
                        || !$collection->acceptsType($item['type'])
                        || !$this->collectionRepository->objectExists($item['type'], $item['id'])
                    ) {
                        continue;
                    }

                    if ($collection->add_item($item['id'], $item['type'])) {
                        ++$added;
                    }
                }

                if ($added > 0) {
                    Ajax::set_include_override(true);

                    ob_start();
                    display_notification(T_('Added to collection'));
                    $results['reloader']      = ob_get_clean();
                    $results['collection_id'] = (string) $collection->getId();
                } else {
                    debug_event('collection.ajax', 'No item to add. Aborting...', 5);
                }
                break;
            case 'delete_track':
                $collection = $this->collectionRepository->findById(
                    (int) $this->requestParser->getFromRequest('collection_id')
                );
                if ($collection === null) {
                    break;
                }

                if ($collection->has_collaborate($user)) {
                    // Members are named by their `collection_map` row, the only address that survives the same
                    // object appearing twice. A multi-select sends every checked row at once, so this renumbers
                    // once at the end rather than per member.
                    $trackIds = array_filter(
                        array_map('intval', explode(',', $this->requestParser->getFromRequest('track_id'))),
                        static fn(int $trackId): bool => $trackId > 0
                    );
                    foreach ($trackIds as $trackId) {
                        $collection->delete_track($trackId);
                    }

                    if ($trackIds !== []) {
                        $collection->regenerate_track_numbers();
                    }
                }

                $browse_id  = (int) $this->requestParser->getFromRequest('browse_id');
                $object_ids = $collection->get_items();
                ob_start();
                $browse = new Browse($browse_id);
                $browse->set_type('collection_items');
                $browse->add_supplemental_object('collection', $collection);
                $browse->save_objects($object_ids);
                $browse->show_objects($object_ids, true);
                $browse->store();

                $results[$browse->get_content_div()] = ob_get_clean();
                break;
        }

        echo xoutput_from_array($results);
    }
}
