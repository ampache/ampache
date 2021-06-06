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
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SetPlayTypeAction implements ActionInterface
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        // Make sure they have the rights to do this
        if (!Preference::has_access('play_type')) {
            $results['rfc3514'] = '0x1';

            return $results;
        }

        switch ($_POST['type']) {
            case 'stream':
            case 'localplay':
            case 'democratic':
                $key = 'allow_' . Core::get_post('type') . '_playback';
                if (!AmpConfig::get($key)) {
                    $results['rfc3514'] = '0x1';

                    return $results;
                }
                $new = Core::get_post('type');
                break;
            case 'web_player':
                $new = 'web_player';
                break;
            default:
                $new                = 'stream';
                $results['rfc3514'] = '0x1';

                return $results;
        } // end switch

        $current = AmpConfig::get('play_type');

        // Go ahead and update their preference
        if (Preference::update('play_type', Core::get_global('user')->id, $new)) {
            AmpConfig::set('play_type', $new, true);
        }

        if (($new == 'localplay' && $current != 'localplay') || ($current == 'localplay' && $new != 'localplay')) {
            $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
        }

        $results['rfc3514'] = '0x0';

        return $results;
    }
}
