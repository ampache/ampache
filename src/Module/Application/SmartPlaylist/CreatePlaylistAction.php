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

namespace Ampache\Module\Application\SmartPlaylist;

use Ampache\Model\Search;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CreatePlaylistAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'create_playlist';

    private UiInterface $ui;

    public function __construct(
        UiInterface $ui
    ) {
        $this->ui = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        if (!Access::check('interface', 25)) {
            Ui::access_denied();

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        foreach ($_REQUEST as $key => $value) {
            $prefix = substr($key, 0, 4);
            $value  = trim($value);

            if ($prefix == 'rule' && strlen($value)) {
                $rules[$key] = Dba::escape($value);
            }
        }

        switch ($_REQUEST['operator']) {
            case 'or':
                $operator = 'OR';
                break;
            default:
                $operator = 'AND';
                break;
        } // end switch on operator

        $playlist_name    = (string) scrub_in($_REQUEST['playlist_name']);

        $playlist = new Search(null, 'song');
        $playlist->parse_rules($data);
        $playlist->logic_operator = $operator;
        $playlist->name           = $playlist_name;
        $playlist->save();
        
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
