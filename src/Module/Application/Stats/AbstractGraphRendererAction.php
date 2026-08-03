<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\Stats;

use Ampache\Config\AmpConfig;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ApplicationException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Graph;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\displayable_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\User;

abstract readonly class AbstractGraphRendererAction implements ApplicationActionInterface
{
    protected function __construct(
        private LibraryItemLoaderInterface $libraryItemLoader,
        private Graph $graph,
    ) {}

    /**
     * @throws ApplicationException
     */
    protected function renderGraph(
        GuiGatekeeperInterface $gatekeeper,
    ): void {
        if (!AmpConfig::get('statistical_graphs')) {
            return;
        }

        // the date/zoom form posts back, so these have to come from the request and not the query
        // string; reading the query alone dropped the object the graphs were scoped to
        $object_type = Core::get_request('object_type');
        $object_id   = (int) Core::get_request('object_id');

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
                $owner_id < 1
                || $owner_id != Core::get_global('user')?->getId()
            )
            && $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) === false
        ) {
            throw new AccessDeniedException();
        }

        $user_id = (int) Core::get_request('user_id');
        $zoom    = (string) ($_REQUEST['zoom'] ?? 'day');

        $end_date = (isset($_REQUEST['end_date']))
            ? (int) strtotime((string) $_REQUEST['end_date'])
            : time();
        // scale the default window to the zoom bucket so a fresh year/month graph spans enough buckets to be readable
        $default_span = match ($zoom) {
            'hour' => 86400,
            'month' => 31536000,
            'year' => 315360000,
            default => 864000,
        };
        $zoom_changed = (($_REQUEST['rendered_zoom'] ?? $zoom) !== $zoom);
        $start_date   = (isset($_REQUEST['start_date']) && !$zoom_changed)
            ? (int) strtotime((string) $_REQUEST['start_date'])
            : ($end_date - $default_span);

        // format for the datetimepicker inputs (Y-m-d H:i) so the field, the picker and strtotime all round-trip
        $f_end_date   = date('Y-m-d H:i', $end_date);
        $f_start_date = date('Y-m-d H:i', $start_date);

        $gtypes   = [];
        $gtypes[] = 'user_hits';
        if (
            in_array($object_type, [null, 'song', 'video'])
        ) {
            $gtypes[] = 'user_bandwidth';
        }

        if (!$user_id && !$object_id) {
            $gtypes[] = 'catalog_files';
            // used within the template
            $gtypes[] = 'catalog_size';
        }

        $blink = '';
        if ($libitem instanceof displayable_item) {
            $f_link = $libitem->get_f_link();
            if ($f_link !== '' && $f_link !== '0') {
                $blink = $f_link;
            }
        } elseif ($user_id > 0) {
            $user  = new User($user_id);
            $blink = $user->get_f_link();
        }

        // named here because the template renders in this scope and cannot reach the container itself
        $graph = $this->graph;

        require_once Ui::find_template('show_graphs.inc.php');
    }
}
