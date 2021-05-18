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

use Ampache\Repository\Model\Tag;

final class TagListUpdater implements TagListUpdaterInterface
{
    private TagCreatorInteface $tagCreator;

    public function __construct(
        TagCreatorInteface $tagCreator
    ) {
        $this->tagCreator = $tagCreator;
    }

    /**
     * Update the tags list based on a comma-separated list
     *  (ex. tag1,tag2,tag3,..)
     * @param string $tags_comma
     * @param string $type
     * @param integer $object_id
     * @param boolean $overwrite
     * @return boolean
     */
    public function update($tags_comma, $type, $object_id, $overwrite)
    {
        if (!strlen((string) $tags_comma) > 0) {
            return false;
        }
        debug_event(self::class, 'Updating tags for values {' . $tags_comma . '} type {' . $type . '} object_id {' . $object_id . '}', 5);

        $ctags       = Tag::get_top_tags($type, $object_id);
        $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $tags_comma);
        $filterunder = str_replace('_', ', ', $filterfolk);
        $filter      = str_replace(';', ', ', $filterunder);
        $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
        $editedTags  = (is_array($filter_list)) ? array_unique($filter_list) : array();

        foreach ($ctags as $ctid => $ctv) {
            if ($ctv['id'] != '') {
                $ctag  = new Tag($ctv['id']);
                $found = false;

                foreach ($editedTags as $tk => $tv) {
                    if ($ctag->name == $tv) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    unset($editedTags[$ctag->name]);
                } else {
                    if ($overwrite && $ctv['user'] == 0) {
                        debug_event(self::class, 'The tag {' . $ctag->name . '} was not found in the new list. Delete it.', 5);
                        $ctag->remove_map($type, $object_id, false);
                    }
                }
            }
        }

        // Look if we need to add some new tags
        foreach ($editedTags as $tk => $tv) {
            if ($tv != '') {
                $this->tagCreator->add($type, $object_id, $tv);
            }
        }

        return true;
    }
}
