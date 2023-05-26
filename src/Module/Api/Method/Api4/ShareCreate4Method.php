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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Share;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Repository\Model\User;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ShareCreateMethod
 */
final class ShareCreate4Method
{
    public const ACTION = 'share_create';

    /**
     * share_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * @param array $input
     * @param User $user
     * filter      = (string) object_id
     * type        = (string) object_type
     * description = (string) description (will be filled for you if empty) //optional
     * expires     = (integer) days to keep active //optional
     * @return boolean
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function share_create(array $input, User $user): bool
    {
        if (!AmpConfig::get('share')) {
            Api4::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, array('type', 'filter'), self::ACTION)) {
            return false;
        }

        $object_id   = $input['filter'];
        $object_type = $input['type'];
        $description = $input['description'] ?? null;
        $expire_days = (isset($input['expires'])) ? filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT) : null;
        // confirm the correct data
        if (!in_array($object_type, array('song', 'album', 'artist'))) {
            Api4::message('error', T_('Wrong object type ' . $object_type), '401', $input['api_format']);

            return false;
        }
        $results = array();
        if (!InterfaceImplementationChecker::is_library_item($object_type) || !$object_id) {
            Api4::message('error', T_('Wrong library item type'), '401', $input['api_format']);
        } else {
            $className = ObjectTypeToClassNameMapper::map($object_type);
            $item      = new $className($object_id);
            if (!$item->id) {
                Api4::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            // @todo Replace by constructor injection
            global $dic;
            $functionChecker   = $dic->get(FunctionCheckerInterface::class);
            $passwordGenerator = $dic->get(PasswordGeneratorInterface::class);

            $results[] = Share::create_share(
                $user->id,
                $object_type,
                $object_id,
                true,
                $functionChecker->check(AccessLevelEnum::FUNCTION_DOWNLOAD),
                $expire_days,
                $passwordGenerator->generate_token(),
                0,
                $description
            );
        }
        Catalog::count_table('share');
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::shares($results);
                break;
            default:
                echo Xml4_Data::shares($results);
        }

        return true;
    } // share_create
}
