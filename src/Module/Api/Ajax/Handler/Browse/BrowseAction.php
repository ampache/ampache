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

namespace Ampache\Module\Api\Ajax\Handler\Browse;

use Ampache\Module\System\Core;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BrowseAction extends AbstractBrowseAction
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $browse     = $this->getBrowse();
        $object_ids = array();

        // Check 'value' with isset because it can null
        //(user type a "start with" word and deletes it)
        if ($_REQUEST['key'] && (isset($_REQUEST['multi_alpha_filter']) || isset($_REQUEST['value']))) {
            // Set any new filters we've just added
            $browse->set_filter($_REQUEST['key'], $_REQUEST['multi_alpha_filter']);
            $browse->set_catalog($_SESSION['catalog']);
        }

        if ($_REQUEST['sort']) {
            // Set the new sort value
            $browse->set_sort($_REQUEST['sort']);
        }

        if ($_REQUEST['catalog_key'] || $_SESSION['catalog'] != 0) {
            $browse->set_filter('catalog', $_REQUEST['catalog_key']);
            $_SESSION['catalog'] = $_REQUEST['catalog_key'];
        } elseif ((int) Core::get_request('catalog_key') == 0) {
            $browse->set_filter('catalog', null);
            unset($_SESSION['catalog']);
        }

        ob_start();
        $browse->show_objects(null, $this->getArgument());
        $results[$browse->get_content_div()] = ob_get_clean();
        $browse->store();

        return $results;
    }
}
