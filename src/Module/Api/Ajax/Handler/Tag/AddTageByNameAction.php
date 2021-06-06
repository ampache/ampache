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

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Tag;

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Module\Tag\TagCreatorInteface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddTageByNameAction implements ActionInterface
{
    private TagCreatorInteface $tagCreator;

    public function __construct(
        TagCreatorInteface $tagCreator
    ) {
        $this->tagCreator = $tagCreator;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        if (!Access::check('interface', 75)) {
            debug_event('tag.ajax', Core::get_global('user')->username . ' attempted to add new tag', 1);

            return [];
        }
        debug_event('tag.ajax', 'Adding new tag by name...', 5);
        $this->tagCreator->add(
            filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES),
            filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT),
            $_GET['tag_name']
        );

        return [];
    }
}
