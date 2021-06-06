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

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Stream;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DirectplayAction implements ActionInterface
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];
        $object_type = Core::get_request('object_type');
        $object_id   = $_GET['object_id'];
        if (is_array($object_id)) {
            $object_id = implode(',', $object_id);
        }
        debug_event('stream.ajax', 'Called for ' . $object_type . ': {' . $object_id . '}', 5);

        if (InterfaceImplementationChecker::is_playable_item($object_type)) {
            $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=play_item&object_type=' . $object_type . '&object_id=' . $object_id;
            if ($_REQUEST['custom_play_action']) {
                $_SESSION['iframe']['target'] .= '&custom_play_action=' . $_REQUEST['custom_play_action'];
            }
            if (!empty($_REQUEST['append'])) {
                $_SESSION['iframe']['target'] .= '&append=true';
            }
            if (!empty($_REQUEST['playnext'])) {
                $_SESSION['iframe']['target'] .= '&playnext=true';
            }
            if ($_REQUEST['subtitle']) {
                $_SESSION['iframe']['subtitle'] = $_REQUEST['subtitle'];
            } else {
                if (isset($_SESSION['iframe']['subtitle'])) {
                    unset($_SESSION['iframe']['subtitle']);
                }
            }
            $results['rfc3514'] = '<script>' . Core::get_reloadutil() . '(\'' . AmpConfig::get('web_path') . '/util.php\');</script>';
        }

        return $results;
    }
}
