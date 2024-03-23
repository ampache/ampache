<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Authorization\Access;
use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Democratic;

final class DemocraticPlaybackAjaxHandler implements AjaxHandlerInterface
{
    private RequestParserInterface $requestParser;

    public function __construct(
        RequestParserInterface $requestParser
    ) {
        $this->requestParser = $requestParser;
    }

    public function handle(): void
    {
        $democratic = Democratic::get_current_playlist();
        $democratic->set_parent();

        $show_browse = false;
        $results     = array();
        $action      = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'delete_vote':
                $democratic->remove_vote($_REQUEST['row_id']);
                $show_browse = true;
                break;
            case 'add_vote':
                $democratic->add_vote(array(
                    array(
                        'object_type' => Core::get_request('type'),
                        'object_id' => filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT)
                    )
                ));
                $show_browse = true;
                break;
            case 'delete':
                if (empty(Core::get_global('user')) || !Core::get_global('user')->has_access(AccessLevelEnum::MANAGER)) {
                    echo (string) xoutput_from_array(array('rfc3514' => '0x1'));

                    return;
                }

                $democratic->delete_votes($_REQUEST['row_id']);
                $show_browse = true;
                break;
            case 'send_playlist':
                if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
                    echo (string) xoutput_from_array(array('rfc3514' => '0x1'));

                    return;
                }

                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=democratic&democratic_id=' . scrub_out($_REQUEST['democratic_id']);
                $results['rfc3514']           = '<script>' . Core::get_reloadutil() . '("' . $_SESSION['iframe']['target'] . '")</script>';
                break;
            case 'clear_playlist':
                if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) {
                    echo (string) xoutput_from_array(array('rfc3514' => '0x1'));

                    return;
                }

                $democratic = new Democratic($_REQUEST['democratic_id']);
                $democratic->set_parent();
                $democratic->clear();

                $show_browse = true;
                break;
            default:
                $results['rfc3514'] = '0x1';
                break;
        } // switch on action;

        if ($show_browse) {
            ob_start();
            $object_ids = $democratic->get_items();
            $browse_id  = (int)($_REQUEST['browse_id'] ?? 0);
            $browse     = new Browse($browse_id);
            $browse->set_type('democratic');
            $browse->set_static_content(false);
            $browse->show_objects($object_ids);
            $browse->store();
            $results[$browse->get_content_div()] = ob_get_contents();
            ob_end_clean();
        }

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
