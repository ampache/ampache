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

namespace Ampache\Module\Api\Ajax\Handler\Defaults;

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SetUserflagAction implements ActionInterface
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];
        if (User::is_registered()) {
            ob_start();
            $flagtype = Core::get_get('userflag_type');
            $flag_id  = filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
            $userflag = new Userflag($flag_id, $flagtype);
            $userflag->set_flag($_GET['userflag']);
            echo Userflag::show($flag_id, $flagtype);
            $key           = "userflag_" . $flag_id . "_" . $flagtype;
            $results[$key] = ob_get_contents();
            ob_end_clean();
        } else {
            $results['rfc3514'] = '0x1';
        }

        return $results;
    }
}
