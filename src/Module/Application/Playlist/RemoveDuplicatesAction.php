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

namespace Ampache\Module\Application\Playlist;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RemoveDuplicatesAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'remove_duplicates';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory
    ) {
        $this->ui           = $ui;
        $this->modelFactory = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $playlist = $this->modelFactory->createPlaylist((int) $_REQUEST['playlist_id']);
        /* Make sure they have permission */
        if (!$playlist->has_access()) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $tracks_to_rm = [];
        $map          = [];
        $items        = $playlist->get_items();
        foreach ($items as $item) {
            if (!array_key_exists($item['object_type'], $map)) {
                $map[$item['object_type']] = [];
            }
            if (!in_array($item['object_id'], $map[$item['object_type']])) {
                $map[$item['object_type']][] = $item['object_id'];
            } else {
                $tracks_to_rm[] = $item['track_id'];
            }
        }

        foreach ($tracks_to_rm as $track_id) {
            $playlist->delete_track($track_id);
        }
        $object_ids = $playlist->get_items();
        require_once Ui::find_template('show_playlist.inc.php');

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
