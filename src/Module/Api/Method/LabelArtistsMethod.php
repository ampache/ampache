<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public Label, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public Label as published by
 * the Free Software Foundation, either version 3 of the Label, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public Label for more details.
 *
 * You should have received a copy of the GNU Affero General Public Label
 * along with this program.  If not, see <https://www.gnu.org/labels/>.
 *
 */

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class LabelArtistsMethod
 * @package Lib\ApiMethods
 */
final class LabelArtistsMethod
{
    public const ACTION = 'label_artists';

    /**
     * label_artists
     * MINIMUM_API_VERSION=420000
     *
     * This returns all artists attached to a label ID
     *
     * filter  = (string) UID of label
     * include = (array|string) 'albums', 'songs' //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * cond    = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     * sort    = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     */
    public static function label_artists(array $input, User $user): bool
    {
        if (!AmpConfig::get('label')) {
            Api::error('Enable: label', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }

        $label = self::getLabelRepository()->findById((int) ($input['filter'] ?? 0));
        if ($label === null) {
            Api::empty('artist', $input['api_format']);

            return false;
        }

        $browse = Api::getBrowse();
        $browse->set_type('artist');

        Api::set_sort(html_entity_decode((string)($input['sort'] ?? '')), ['name','ASC'], $browse);

        $browse->set_filter('label', $label->getId());

        Api::set_conditions(html_entity_decode((string)($input['cond'] ?? '')), $browse);

        $results = $browse->get_objects();
        if (empty($results)) {
            Api::empty('artist', $input['api_format']);

            return false;
        }

        $include = [];
        if (array_key_exists('include', $input)) {
            $include = (is_array($input['include'])) ? $input['include'] : explode(',', html_entity_decode((string)($input['include'])));
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::artists($results, $include, $user);
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::artists($results, $include, $user);
        }

        return true;
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getLabelRepository(): LabelRepositoryInterface
    {
        global $dic;

        return $dic->get(LabelRepositoryInterface::class);
    }
}
