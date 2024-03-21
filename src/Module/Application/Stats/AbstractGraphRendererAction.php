<?php

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

namespace Ampache\Module\Application\Stats;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ApplicationException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\User;

abstract readonly class AbstractGraphRendererAction implements ApplicationActionInterface
{
    protected function __construct(
        private LibraryItemLoaderInterface $libraryItemLoader
    ) {
    }

    /**
     * @throws ApplicationException
     */
    protected function renderGraph(
        GuiGatekeeperInterface $gatekeeper
    ): void {
        $object_type = Core::get_request('object_type');
        $object_id   = (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);

        $libitem  = null;
        $owner_id = 0;

        if ($object_id && $object_type !== '') {
            $libitem = $this->libraryItemLoader->load(
                LibraryItemEnum::from($object_type),
                $object_id
            );

            if ($libitem !== null) {
                $owner_id = $libitem->get_user_owner();
            }
        }

        if (
            (
                $owner_id < 1 ||
                $owner_id != Core::get_global('user')?->getId()
            ) &&
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) === false
        ) {
            throw new AccessDeniedException();
        }

        $user_id      = (int)Core::get_request('user_id');
        $end_date     = (isset($_REQUEST['end_date'])) ? (int)strtotime((string)$_REQUEST['end_date']) : time();
        $f_end_date   = get_datetime((int)$end_date);
        $start_date   = (isset($_REQUEST['start_date'])) ? (int)strtotime((string)$_REQUEST['start_date']) : ($end_date - 864000);
        $f_start_date = get_datetime((int)$start_date);
        $zoom         = (string)($_REQUEST['zoom'] ?? 'day');

        $gtypes   = array();
        $gtypes[] = 'user_hits';
        if ($object_type == null || $object_type == 'song' || $object_type == 'video') {
            $gtypes[] = 'user_bandwidth';
        }
        if (!$user_id && !$object_id) {
            $gtypes[] = 'catalog_files';
            $gtypes[] = 'catalog_size';
        }

        $blink = '';
        if ($libitem !== null) {
            $f_link = $libitem->get_f_link();
            if (!empty($f_link)) {
                $blink = $f_link;
            }
        } elseif ($user_id > 0) {
            $user  = new User($user_id);
            $blink = $user->get_f_link();
        }

        require_once Ui::find_template('show_graphs.inc.php');
    }
}
