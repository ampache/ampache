<?php

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

// wunderbaum.js file system browser

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Module\System\Core;
use Ampache\Module\Util\FileSystem;
use Ampache\Module\Util\Upload;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Psr\Container\ContainerInterface;

define('AJAX_INCLUDE', '1');

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../../src/Config/Init.php';

$current_user = Core::get_global('user');
if (!$current_user instanceof User) {
    return false;
}

$catalog_id = (int)AmpConfig::get('upload_catalog', 0);
$catalog    = Catalog::create_from_id($catalog_id);

$rootdir = ($catalog instanceof Catalog_local)
    ? Upload::get_root($catalog, $current_user->username)
    : null;

if ($rootdir === null) {
    return false;
}

$rootdir .= DIRECTORY_SEPARATOR;

if (isset($_GET['operation'])) {
    try {
        $access_level = AccessLevelEnum::tryFrom(
            (int) AmpConfig::get(ConfigurationKeyEnum::UPLOAD_ACCESS_LEVEL)
        ) ?? AccessLevelEnum::USER;

        if (
            AmpConfig::get(ConfigurationKeyEnum::ALLOW_UPLOAD, false) === false ||
            $access_level === AccessLevelEnum::DEFAULT ||
            !Access::check(AccessTypeEnum::INTERFACE, $access_level) ||
            AmpConfig::get(ConfigurationKeyEnum::DEMO_MODE) === true
        ) {
            throw new AccessDeniedException();
        }

        $fs   = new FileSystem($rootdir);
        $rslt = null;
        $node = (isset($_GET['id']) && $_GET['id'] !== '#')
            ? (string)$_GET['id']
            : '/';
        switch (Core::get_get('operation')) {
            case 'get_node':
                $rslt = $fs->lst($node, (isset($_GET['id']) && $_GET['id'] === '#'));
                break;
            case 'get_content':
                $rslt = $fs->data($node);
                break;
            case 'create_node':
                $rslt = $fs->create($node, $_GET['text'] ?? '', (!isset($_GET['type']) || filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) !== 'file'));
                break;
            case 'rename_node':
                $rslt = $fs->rename($node, $_GET['text'] ?? '');
                break;
            case 'delete_node':
                $rslt = $fs->remove($node);
                break;
            case 'move_node':
                $parn = (isset($_GET['parent']) && $_GET['parent'] !== '#')
                    ? (string)$_GET['parent']
                    : '/';
                $rslt = $fs->move($node, $parn);
                break;
            case 'copy_node':
                $parn = (isset($_GET['parent']) && $_GET['parent'] !== '#')
                    ? (string)$_GET['parent']
                    : '/';
                $rslt = $fs->copy($node, $parn);
                break;
            default:
                throw new Exception('Unsupported operation: ' . Core::get_get('operation'));
        }
        header('Content-Type: application/json; charset=utf8');
        echo json_encode($rslt);
    } catch (Exception $error) {
        header(Core::get_server('SERVER_PROTOCOL') . ' 500 Server Error');
        header('Status:  500 Server Error');
        echo $error->getMessage();
    }
    die();
}
