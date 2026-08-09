<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Config\AmpConfig;
use Ampache\Gui\Search\RandomFormView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetAdvancedAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'get_advanced';

    public function __construct(
        private UiInterface $ui,
        private VideoRepositoryInterface $videoRepository,
        private BrowseFactoryInterface $browseFactory,
        private Random $random,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $objectIds = [];
        // The form posts an empty type when nothing is selected.
        $objectType = LibraryItemEnum::tryFrom((string) ($_REQUEST['type'] ?? '')) ?? LibraryItemEnum::SONG;

        $user = Core::get_global('user');
        if ($user instanceof User) {
            $user->load_playlist();
            $objectIds = $this->random->advanced($objectType->value, $_POST);
            if ($objectIds !== []) {
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
        echo (new RandomFormView(
            $objectIds,
            $this->browseFactory,
            $this->videoRepository,
            AmpConfig::get_web_path('/client')
        ))->render();
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
