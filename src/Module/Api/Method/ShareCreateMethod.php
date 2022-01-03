<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Share;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\User\PasswordGenerator;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;

/**
 * Class ShareCreateMethod
 * @package Lib\ApiMethods
 */
final class ShareCreateMethod
{
    public const ACTION = 'share_create';

    /**
     * share_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * @param array $input
     * filter      = (string) object_id
     * type        = (string) object_type ('song', 'album', 'artist')
     * description = (string) description (will be filled for you if empty) //optional
     * expires     = (integer) days to keep active //optional
     * @return boolean
     */
    public static function share_create(array $input): bool
    {
        if (!AmpConfig::get('share')) {
            Api::error(T_('Enable: share'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('type', 'filter'), self::ACTION)) {
            return false;
        }

        $description = $input['description'];
        $object_id   = $input['filter'];
        $type        = $input['type'];
        $expire_days = Share::get_expiry($input['expires']);
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist'))) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(T_('Bad Request'), '4710', self::ACTION, $type, $input['api_format']);

            return false;
        }

        $className = ObjectTypeToClassNameMapper::map($type);

        $share = array();
        if (!$className || !$object_id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(T_('Bad Request'), '4710', self::ACTION, $type, $input['api_format']);
        } else {
            $item = new $className($object_id);
            if (!$item->id) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'filter', $input['api_format']);

                return false;
            }
            // @todo Replace by constructor injection
            global $dic;
            $functionChecker   = $dic->get(FunctionCheckerInterface::class);
            $passwordGenerator = $dic->get(PasswordGeneratorInterface::class);

            $share[] = Share::create_share(
                $type,
                $object_id,
                true,
                $functionChecker->check(AccessLevelEnum::FUNCTION_DOWNLOAD),
                $expire_days,
                $passwordGenerator->generate(PasswordGenerator::DEFAULT_LENGTH),
                0,
                $description
            );
        }
        if (empty($share)) {
            Api::error(T_('Bad Request'), '4710', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        Catalog::count_table('share');
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::shares($share, false);
                break;
            default:
                echo Xml_Data::shares($share);
        }

        return true;
    }
}
