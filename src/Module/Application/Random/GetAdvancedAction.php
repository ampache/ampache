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

namespace Ampache\Module\Application\Random;

use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Random;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\User;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetAdvancedAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'get_advanced';

    private UiInterface $ui;

    private VideoRepositoryInterface $videoRepository;

    public function __construct(
        UiInterface $ui,
        VideoRepositoryInterface $videoRepository
    ) {
        $this->ui              = $ui;
        $this->videoRepository = $videoRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $objectIds  = array();
        $objectType = LibraryItemEnum::from($_REQUEST['type'] ?? 'song');

        $user = Core::get_global('user');
        if ($user instanceof User) {
            $user->load_playlist();
            $objectIds = Random::advanced($objectType->value, $_POST);
            if (!empty($objectIds)) {
                // you need to add by the base child type song/video
                $objectType = match ($objectType->value) {
                    'album', 'artist' => LibraryItemEnum::SONG,
                    default => $objectType,
                };
                // We need to add them to the active playlist
                foreach ($objectIds as $object_id) {
                    $user->playlist?->add_object($object_id, $objectType);
                }
            }
        }

        $this->ui->showHeader();
        $this->ui->show(
            'show_random.inc.php',
            [
                'videoRepository' => $this->videoRepository,
                'object_ids' => $objectIds
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
