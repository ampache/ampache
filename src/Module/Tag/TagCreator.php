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

declare(strict_types=1);

namespace Ampache\Module\Tag;

use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\TagRepositoryInterface;

final class TagCreator implements TagCreatorInteface
{
    private TagRepositoryInterface $tagRepository;

    public function __construct(
        TagRepositoryInterface $tagRepository
    ) {
        $this->tagRepository = $tagRepository;
    }

    /**
     * This is a wrapper function, it figures out what we need to add, be it a tag
     * and map, or just the mapping
     */
    public function add(
        string $type,
        int $object_id,
        string $value
    ): ?int {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return null;
        }

        if (!is_numeric($object_id)) {
            return null;
        }

        $cleaned_value = str_replace('Folk, World, & Country', 'Folk World & Country', $value);

        if (!strlen((string)$cleaned_value)) {
            return null;
        }

        // Check and see if the tag exists, if not create it, we need the tag id from this
        if (!$tag_id = $this->tagRepository->findByName($cleaned_value)) {
            debug_event(self::class, 'Adding new tag {' . $cleaned_value . '}', 5);
            $tag_id = Tag::add_tag($cleaned_value);
        }

        if (!$tag_id) {
            debug_event(self::class, 'Error unable to create tag value:' . $cleaned_value . ' unknown error', 1);

            return null;
        }

        // We've got the tag id, let's see if it's already got a map, if not then create the map and return the value
        if (!$map_id = $this->tag_map_exists($type, $object_id, (int)$tag_id, 0)) {
            $map_id = $this->add_tag_map($type, $object_id, (int)$tag_id, false);
        }

        return (int)$map_id;
    }

    /**
     * add_tag_map
     * This adds a specific tag to the map for specified object
     * @param string $type
     * @param integer|string $object_id
     * @param integer|string $tag_id
     * @param boolean $user
     * @return boolean|string|null
     */
    public function add_tag_map($type, $object_id, $tag_id, $user = true)
    {
        if ($user === true) {
            $uid = (int)(Core::get_global('user')->id);
        } else {
            $uid = (int)($user);
        }

        if (!InterfaceImplementationChecker::is_library_item($type)) {
            debug_event(__CLASS__, $type . " is not a library item.", 3);

            return false;
        }
        $tag_id  = (int)($tag_id);
        $item_id = (int)($object_id);

        if (!$tag_id || !$item_id) {
            return false;
        }

        // If tag merged to another one, add reference to the merge destination
        $parent = new Tag($tag_id);
        $merges = $parent->get_merged_tags();
        if (!$parent->is_hidden) {
            $merges[] = array('id' => $parent->id, 'name' => $parent->name);
        }
        foreach ($merges as $tag) {
            $sql = "INSERT INTO `tag_map` (`tag_id`, `user`, `object_type`, `object_id`) " . "VALUES (?, ?, ?, ?)";
            Dba::write($sql, array($tag['id'], $uid, $type, $item_id));
        }

        return (int) Dba::insert_id();
    } // add_tag_map

    /**
     * This looks to see if the current mapping of the current object of the current tag of the current
     * user exists, lots of currents... taste good in scones.
     * @param string $type
     * @param integer $object_id
     * @param integer $tag_id
     * @param integer $user
     * @return boolean|mixed
     */
    public function tag_map_exists($type, $object_id, $tag_id, $user)
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            debug_event(__CLASS__, 'Requested type is not a library item.', 3);

            return false;
        }

        $sql        = "SELECT * FROM `tag_map` LEFT JOIN `tag` ON `tag`.`id` = `tag_map`.`tag_id` LEFT JOIN `tag_merge` ON `tag`.`id`=`tag_merge`.`tag_id` " . "WHERE (`tag_map`.`tag_id` = ? OR `tag_map`.`tag_id` = `tag_merge`.`merged_to`) AND `tag_map`.`user` = ? AND `tag_map`.`object_id` = ? AND `tag_map`.`object_type` = ?";
        $db_results = Dba::read($sql, array($tag_id, $user, $object_id, $type));

        $results = Dba::fetch_assoc($db_results);

        return $results['id'];
    }
}
