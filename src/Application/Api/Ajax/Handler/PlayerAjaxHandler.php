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

use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Broadcast;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\User;

final readonly class PlayerAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private AjaxUriRetrieverInterface $ajaxUriRetriever,
        private UiInterface $ui
    ) {
    }

    public function handle(User $user): void
    {
        $results = array();
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'show_broadcasts':
                ob_start();
                $ajaxUri = $this->ajaxUriRetriever->getAjaxUri();
                $this->ui->show(
                    'show_broadcasts_dialog.inc.php',
                    ['ajaxUri' => $ajaxUri]
                );
                $results = ob_get_contents();
                ob_end_clean();
                echo $results;

                return;
            case 'broadcast':
                $broadcast_id = Core::get_get('broadcast_id');
                if (empty($broadcast_id)) {
                    $broadcast_id = Broadcast::create(T_('My Broadcast'));
                }

                $broadcast = new Broadcast((int) $broadcast_id);
                if ($broadcast->isNew() === false) {
                    $key = Broadcast::generate_key();
                    $broadcast->update_state(true, $key);
                    $results['broadcast'] = Broadcast::get_unbroadcast_link((int) $broadcast_id) . '<script>startBroadcast(\'' . $key . '\');</script>';
                }
                break;
            case 'unbroadcast':
                $broadcast_id = Core::get_get('broadcast_id');
                $broadcast    = new Broadcast((int) $broadcast_id);
                if ($broadcast->isNew() === false) {
                    $broadcast->update_state(false);
                    $results['broadcast'] = Broadcast::get_broadcast_link() . '<script>stopBroadcast();</script>';
                }
                break;
            default:
                break;
        } // switch on action;

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
