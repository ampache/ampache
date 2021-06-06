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
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddTagAction implements ActionInterface
{
    private ModelFactoryInterface $modelFactory;

    private TagCreatorInteface $tagCreator;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        TagCreatorInteface $tagCreator
    ) {
        $this->modelFactory = $modelFactory;
        $this->tagCreator   = $tagCreator;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        if (!$this->can_edit_tag_map(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES), filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), false)) {
            debug_event('tag.ajax', Core::get_global('user')->username . ' attempted to add unauthorized tag map', 1);

            return [];
        }
        debug_event('tag.ajax', 'Adding new tag...', 5);
        $this->tagCreator->add_tag_map(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES), (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), (int) $_GET['tag_id'], false);

        return [];
    }

    /**
     * can_edit_tag_map
     * @param string $object_type
     * @param integer $object_id
     * @param string|boolean $user
     * @return boolean
     */
    private function can_edit_tag_map($object_type, $object_id, $user = true)
    {
        if ($user === true) {
            $uid = (int)(Core::get_global('user')->id);
        } else {
            $uid = (int)($user);
        }

        if ($uid > 0) {
            return Access::check('interface', 25);
        }

        if (Access::check('interface', 75)) {
            return true;
        }

        if (InterfaceImplementationChecker::is_library_item($object_type)) {
            /** @var library_item $libitem */
            $libitem    = $this->modelFactory->mapObjectType($object_type, (int) $object_id);
            $owner      = $libitem->get_user_owner();

            return ($owner !== null && $owner == $uid);
        }

        return false;
    }
}
