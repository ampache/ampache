<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\User\Authorization\UserKeyGeneratorInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Module\Util\Mailer;

/**
 * Class UserUpdateMethod
 * @package Lib\ApiMethods
 */
final class UserEditMethod
{
    public const ACTION = 'user_edit';

    /**
     * user_edit
     * MINIMUM_API_VERSION=600000
     *
     * Update an existing user.
     * Takes the username with optional parameters.
     *
     * @param array $input
     * @param User $user
     * username          = (string) $username
     * password          = (string) hash('sha256', $password)) //optional
     * fullname          = (string) $fullname //optional
     * email             = (string) $email //optional
     * website           = (string) $website //optional
     * state             = (string) $state //optional
     * city              = (string) $city //optional
     * disable           = (integer) 0,1 true to disable, false to enable //optional
     * group             = (integer) Catalog filter group for the new user //optional, default = 0
     * maxbitrate        = (integer) $maxbitrate //optional
     * fullname_public   = (integer) 0,1 true to enable, false to disable using fullname in public display //optional
     * reset_apikey      = (integer) 0,1 true to reset a user Api Key //optional
     * reset_streamtoken = (integer) 0,1 true to reset a user Stream Token //optional
     * clear_stats       = (integer) 0,1 true reset all stats for this user //optional
     * @return boolean
     */
    public static function user_edit(array $input, User $user): bool
    {
        if (!Api::check_access('interface', 100, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username             = $input['username'];
        $password             = $input['password'] ?? null;
        $fullname             = $input['fullname'] ?? null;
        $email                = (array_key_exists('email', $input)) ? urldecode($input['email']) : null;
        $website              = $input['website'] ?? null;
        $state                = $input['state'] ?? null;
        $city                 = $input['city'] ?? null;
        $disable              = $input['disable'] ?? null;
        $catalog_filter_group = $input['group'] ?? null;
        $maxbitrate           = (int)($input['maxBitRate'] ?? 0);
        $fullname_public      = $input['fullname_public'] ?? null;
        $reset_apikey         = $input['reset_apikey'] ?? null;
        $reset_streamtoken    = $input['reset_streamtoken'] ?? null;
        $clear_stats          = $input['clear_stats'] ?? null;

        // identify the user to modify
        $update_user = User::get_from_username($username);
        $user_id     = $update_user->getId();

        if ($password && $update_user->access == 100) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $username), '4710', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        $userStateToggler = static::getUserStateToggler();

        if ($user_id > 0) {
            if ($password && !AmpConfig::get('simple_user_mode')) {
                $update_user->update_password('', $password);
            }
            if ($fullname) {
                $update_user->update_fullname($fullname);
            }
            if (Mailer::validate_address($email)) {
                $update_user->update_email($email);
            }
            if ($website) {
                $update_user->update_website($website);
            }
            if ($state) {
                $update_user->update_state($state);
            }
            if ($city) {
                $update_user->update_city($city);
            }
            if ($disable === '1') {
                $userStateToggler->disable($update_user);
            } elseif ($disable === '0') {
                $userStateToggler->enable($update_user);
            }
            if ($catalog_filter_group !== null) {
                $update_user->update_catalog_filter_group((int)$catalog_filter_group);
            }
            if ($maxbitrate > 0) {
                Preference::update('transcode_bitrate', $user_id, $maxbitrate);
            }
            if ($fullname_public !== null) {
                $update_user->update_fullname_public($fullname_public);
            }
            if ($reset_apikey) {
                static::getUserKeyGenerator()->generateApikey($update_user);
            }
            if ($reset_streamtoken) {
                static::getUserKeyGenerator()->generateStreamToken($update_user);
            }
            if ($clear_stats) {
                Stats::clear($update_user->id);
            }
            Api::message('successfully updated: ' . $username, $input['api_format']);

            return true;
        }
        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        Api::error(sprintf(T_('Bad Request: %s'), $username), '4710', self::ACTION, 'system', $input['api_format']);

        return false;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getUserStateToggler(): UserStateTogglerInterface
    {
        global $dic;

        return $dic->get(UserStateTogglerInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getUserKeyGenerator(): UserKeyGeneratorInterface
    {
        global $dic;

        return $dic->get(UserKeyGeneratorInterface::class);
    }
}
