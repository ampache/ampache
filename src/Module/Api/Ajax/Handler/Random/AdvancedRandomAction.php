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

namespace Ampache\Module\Api\Ajax\Handler\Random;

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AdvancedRandomAction implements ActionInterface
{
    private UiInterface $ui;

    public function __construct(
        UiInterface $ui
    ) {
        $this->ui = $ui;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $object_ids = Random::advanced('song', $_POST);
        $results    = [];

        // First add them to the active playlist
        if (!empty($object_ids)) {
            foreach ($object_ids as $object_id) {
                $user->playlist->add_object($object_id, 'song');
            }
        }
        $results['rightbar'] = $this->ui->ajaxInclude('rightbar.inc.php');

        // Now setup the browse and show them below!
        $browse = new Browse();
        $browse->set_type('song');
        $browse->save_objects($object_ids);
        ob_start();
        $browse->show_objects();
        $results['browse'] = ob_get_contents();
        ob_end_clean();

        return $results;
    }
}
