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

namespace Ampache\Module\Application\Playlist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Model\Catalog;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ImportPlaylistAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'import_playlist';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        /* first we rename the file to it's original name before importing.
        Otherwise the playlist name will have the $_FILES['filename']['tmp_name'] which doesn't look right... */
        $dir      = dirname($_FILES['filename']['tmp_name']) . "/";
        $filename = $dir . basename($_FILES['filename']['name']);
        move_uploaded_file($_FILES['filename']['tmp_name'], $filename);

        $result = Catalog::import_playlist($filename);

        if ($result['success']) {
            $url   = 'show_playlist&amp;playlist_id=' . $result['id'];
            $title = T_('No Problem');
            $body  = basename($_FILES['filename']['name']);
            $body .= '<br />' .
                /* HINT: Number of songs */
                sprintf(nT_('Successfully imported playlist with %d song.', 'Successfully imported playlist with %d songs.', $result['count']), $result['count']);
        } else {
            $url   = 'show_import_playlist';
            $title = T_("There Was a Problem");
            $body  = T_('The Playlist could not be imported') . ': ' . $result['error'];
        }
        $this->ui->showConfirmation(
            $title,
            $body,
            sprintf('%s/playlist.php?action=%s', $this->configContainer->getWebPath(), $url)
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
