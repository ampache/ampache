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

namespace Ampache\Module\Tag;

use Ampache\Repository\TagRepositoryInterface;

final class TagListCleaner implements TagListCleanerInterface
{
    private TagRepositoryInterface $tagRepository;

    public function __construct(
        TagRepositoryInterface $tagRepository
    ) {
        $this->tagRepository = $tagRepository;
    }

    /**
     * Clean tag list to existing tag list only
     * @param array|string $tags
     * @return array|string
     */
    public function clean($tags)
    {
        if (is_array($tags)) {
            $taglist = $tags;
        } else {
            $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $tags);
            $filterunder = str_replace('_', ', ', $filterfolk);
            $filter      = str_replace(';', ', ', $filterunder);
            $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
            $taglist     = (is_array($filter_list)) ? array_unique($filter_list) : array();
        }

        $ret = array();
        foreach ($taglist as $tag) {
            $tag = trim((string)$tag);
            if (!empty($tag)) {
                if ($this->tagRepository->findByName($tag)) {
                    $ret[] = $tag;
                }
            }
        }

        return (is_array($tags) ? $ret : implode(",", $ret));
    }
}
