<?php

declare(strict_types=0);

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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;

/**
 * Class SearchRules6Method
 */
final class SearchRules6Method
{
    public const ACTION = 'search_rules';

    /**
     * search_rules
     * MINIMUM_API_VERSION=6.8.0
     *
     * Print a list of valid search rules for your search type
     *
     * filter = (string) 'song', 'album', 'song_artist', 'album_artist', 'artist', 'label', 'playlist', 'podcast', 'podcast_episode', 'genre', 'user', 'video'
     *
     * @param array<string, mixed> $input
     * @param User $user
     * @return bool
     */
    public static function search_rules(array $input, User $user): bool
    {
        if (!Api6::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }

        $type = $input['filter'];
        // confirm the correct data
        if (!in_array(strtolower($type), Search::VALID_TYPES)) {
            Api6::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api6::error('Enable: video', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        if ($type == 'label' && !AmpConfig::get('label')) {
            Api6::error('Enable: label', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        $search  = new Search(0, $type, $user);
        $results = $search->get_rule_types();

        switch ($input['api_format']) {
            case 'json':
                echo json_encode(['rule' => $results], JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml6_Data::object_array($results, 'rule');
        }

        return true;
    }
}
