<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;

/**
 * Class TagArtists3Method
 */
final class TagArtists3Method
{
    public const ACTION = 'tag_artists';

    /**
     * tag_artists
     * This returns the artists associated with the tag in question as defined by the UID
     * @param array $input
     * @param User $user
     */
    public static function tag_artists(array $input, User $user)
    {
        $results = Tag::get_tag_objects('artist', $input['filter']);
        if (!empty($results)) {
            Xml3_Data::set_offset($input['offset'] ?? 0);
            Xml3_Data::set_limit($input['limit'] ?? 0);

            ob_end_clean();
            echo Xml3_Data::artists($results, array(), $user);
        }
    } // tag_artists
}
