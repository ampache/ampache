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

use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OptionsAction extends AbstractBrowseAction
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $option = $_REQUEST['option'];
        $value  = $_REQUEST['value'];

        $browse = $this->getBrowse();

        switch ($option) {
            case 'use_pages':
                $value = ($value == 'true');
                $browse->set_use_pages($value);
                if ($value) {
                    $browse->set_start(0);
                }
                break;
            case 'use_alpha':
                $value = ($value == 'true');
                $browse->set_use_alpha($value);
                $browse->set_start(0);
                if ($value) {
                    $browse->set_filter('regex_match', '^A');
                } else {
                    $browse->set_filter('regex_not_match', '');
                }
                break;
            case 'grid_view':
                /**
                 * The `grid view` is implemented inverted, so apply an inverted logic.
                 * This ensures the `grid view` checkbox behaves as expected
                 */
                $value = ($value == 'false');
                $browse->set_grid_view($value);
                break;
            case 'limit':
                $value = (int) ($value);
                if ($value > 0) {
                    $browse->set_offset($value);
                }
                break;
            case 'custom':
                $value = (int) ($value);
                $limit = $browse->get_offset();
                if ($limit > 0 && $value > 0) {
                    $total = $browse->get_total();
                    $pages = ceil($total / $limit);

                    if ($value <= $pages) {
                        $offset = ($value - 1) * $limit;
                        $browse->set_start($offset);
                    }
                }
                break;
        }

        ob_start();
        $browse->show_objects(null, $this->getArgument());
        $results[$browse->get_content_div()] = ob_get_clean();

        $browse->store();

        return $results;
    }
}
