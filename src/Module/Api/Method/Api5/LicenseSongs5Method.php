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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\SongRepositoryInterface;

/**
 * Class LicenseSongs5Method
 */
final class LicenseSongs5Method
{
    public const ACTION = 'license_songs';

    /**
     * license_songs
     * MINIMUM_API_VERSION=420000
     *
     * This returns all songs attached to a license ID
     *
     * @param array $input
     * @param User $user
     * filter = (string) UID of license
     * @return boolean
     */
    public static function license_songs(array $input, User $user): bool
    {
        if (!AmpConfig::get('licensing')) {
            Api5::error(T_('Enable: licensing'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api5::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $results = static::getSongRepository()->getByLicense((int) scrub_in($input['filter']));
        if (empty($results)) {
            Api5::empty('song', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::songs($results, $user);
                break;
            default:
                echo Xml5_Data::songs($results, $user);
        }

        return true;
    }

    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
