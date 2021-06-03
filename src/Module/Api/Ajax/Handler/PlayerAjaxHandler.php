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
 *
 */

declare(strict_types=0);

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
 *
 */

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\BroadcastRepositoryInteface;
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function T_;
use function xoutput_from_array;

final class PlayerAjaxHandler implements AjaxHandlerInterface
{
    private BroadcastRepositoryInteface $broadcastRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        BroadcastRepositoryInteface $broadcastRepository,
        ModelFactoryInterface $modelFactory
    ) {
        $this->broadcastRepository = $broadcastRepository;
        $this->modelFactory        = $modelFactory;
    }

    public function handle(
        ServerRequestInterface $reqest,
        ResponseInterface $response
    ): void {
        $results = array();
        $action  = Core::get_request('action');

        // Switch on the actions
        switch ($_REQUEST['action']) {
            case 'show_broadcasts':
                ob_start();
                require Ui::find_template('show_broadcasts_dialog.inc.php');
                $results = ob_get_contents();
                ob_end_clean();
                echo $results;

                return;
            case 'broadcast':
                $broadcast_id = Core::get_get('broadcast_id');
                if (empty($broadcast_id)) {
                    $broadcast_id = $this->broadcastRepository->create(
                        Core::get_global('user')->getId(),
                        T_('My Broadcast')
                    );
                }

                $broadcast = $this->modelFactory->createBroadcast((int) $broadcast_id);
                if ($broadcast->isNew() === false) {
                    $key  = Broadcast::generate_key();
                    $broadcast->update_state(true, $key);
                    $results['broadcast'] = Broadcast::get_unbroadcast_link((int) $broadcast_id) . '' .
                        '<script>startBroadcast(\'' . $key . '\');</script>';
                }
                break;
            case 'unbroadcast':
                $broadcast_id = Core::get_get('broadcast_id');
                $broadcast    = $this->modelFactory->createBroadcast((int) $broadcast_id);
                if ($broadcast->isNew() === false) {
                    $broadcast->update_state(false);
                    $results['broadcast'] = Broadcast::get_broadcast_link() . '' .
                        '<script>stopBroadcast();</script>';
                }
                break;
            default:
                $results['rfc3514'] = '0x1';
                break;
        } // switch on action;

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
