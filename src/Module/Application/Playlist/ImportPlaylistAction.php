<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Playlist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Catalog\PlaylistImporter;
use Ampache\Module\System\Core;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
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

        // first we rename the file to it's original name before importing.
        // Otherwise the playlist name will have the $_FILES['filename']['tmp_name'] which doesn't look right...
        $dir      = dirname($_FILES['filename']['tmp_name']) . "/";
        $filename = $dir . basename($_FILES['filename']['name']);
        move_uploaded_file($_FILES['filename']['tmp_name'], $filename);
        // allow setting public or private for your imports
        $playlist_type = (string) filter_input(INPUT_POST, 'playlist_visibility', FILTER_SANITIZE_SPECIAL_CHARS);

        $user   = Core::get_global('user');
        $result = ($user instanceof User)
            ? PlaylistImporter::import_playlist($filename, $user->getId(), $playlist_type)
            : null;

        if ($result !== null) {
            $url   = 'show_playlist&playlist_id=' . $result['id'];
            $title = T_('No Problem');
            $body  = basename($_FILES['filename']['name']);
            $body .= '<br />' .
                /* HINT: Number of songs */
                sprintf(nT_("Successfully imported playlist with %d song.", "Successfully imported playlist with %d songs.", $result['count']), $result['count']);
            if (!empty($result['results'])) {
                $body .= "<table class=\"tabledata striped-rows\">\n<thead><tr class=\"th-top\">\n<th>" . T_('Track') . "</th><th>" . T_('File') . "</th><th>" . T_('Status') . "</th>\n<tbody>\n";
                foreach ($result['results'] as $file) {
                    if ($file['found']) {
                        $body .= "<tr>\n<td>" . $file['track'] . "</td><td>" . scrub_out($file['file']) . "</td><td>" . T_('Success') . "</td>\n</tr>\n";
                    } else {
                        $body .= "<tr><td></td><td>" . scrub_out($file['file']) . "</td><td>" . T_('Failure') . "</td></tr>\n";
                    }
                    flush();
                } // foreach songs
                $body .= "</tbody></table>\n";
            }
        } else {
            $url   = 'show_import_playlist';
            $title = T_('There Was a Problem');
            $body  = T_('The Playlist could not be imported') . ': ' . T_('No valid songs found in playlist file');
        }
        $this->ui->showConfirmation(
            $title,
            $body,
            sprintf('%s/playlist.php?action=%s', $this->configContainer->getWebPath('/client'), $url)
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
