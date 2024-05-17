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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;

/**
 * Class LabelArtists5Method
 */
final class LabelArtists5Method
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
     */
    public static function label_artists(array $input, User $user): bool
    {
        if (!AmpConfig::get('label')) {
            Api5::error(T_('Enable: label'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api5::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $include = [];
        if (array_key_exists('include', $input)) {
            $include = (is_array($input['include'])) ? $input['include'] : explode(',', html_entity_decode((string)($input['include'])));
        }

        $label = self::getLabelRepository()->findById((int) $input['filter']);

        if ($label === null) {
            Api5::empty('artist', $input['api_format']);

            return false;
        }

        $results = $label->get_artists();
        if (empty($results)) {
            Api5::empty('artist', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::artists($results, $include, $user);
                break;
            default:
                echo Xml5_Data::artists($results, $include, $user);
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
