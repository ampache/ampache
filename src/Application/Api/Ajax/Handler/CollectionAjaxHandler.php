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

use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Browse;
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
