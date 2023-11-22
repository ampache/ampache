<?php

/*
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

declare(strict_types=0);

namespace Ampache\Module\Application\Admin\Export;

use Ampache\Repository\Model\Catalog;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ExportAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'export';

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false) {
            throw new AccessDeniedException();
        }
        // This may take a while
        set_time_limit(0);

        // Clear everything we've done so far
        ob_end_clean();

        // This will disable buffering so contents are sent immediately to browser.
        // This is very useful for large catalogs because it will immediately display the download dialog to user,
        // instead of waiting until contents are generated, which could take a long time.
        ob_implicit_flush(1);

        header('Content-Transfer-Encoding: binary');
        header('Cache-control: public');

        $date = get_datetime(time(), 'short', 'none', 'y-MM-dd');

        switch ($_REQUEST['export_format']) {
            case 'itunes':
                header("Content-Type: application/itunes+xml; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"ampache-itunes-$date.xml\"");
                Catalog::export('itunes', $_REQUEST['export_catalog']);
                break;
            case 'csv':
                header("Content-Type: application/vnd.ms-excel");
                header("Content-Disposition: filename=\"ampache-export-$date.csv\"");
                Catalog::export('csv', $_REQUEST['export_catalog']);
                break;
        } // end switch on format

        return null;
    }
}
