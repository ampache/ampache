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
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BasketAction implements ActionInterface
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        // Go ahead and see if we should clear the playlist here or not,
        // we might not actually clear it in the session.
        if (($_REQUEST['playlist_method'] == 'clear' || AmpConfig::get('playlist_method') == 'clear')) {
            define('NO_SONGS', '1');
            ob_start();
            require_once Ui::find_template('rightbar.inc.php');
            $results['rightbar'] = ob_get_clean();
        }

        // We need to set the basket up!
        $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=basket&playlist_method=' . scrub_out($_REQUEST['playlist_method']);
        $results['rfc3514']           = '<script>' . Core::get_reloadutil() . '(\'' . AmpConfig::get('web_path') . '/util.php\');</script>';

        return $results;
    }
}
