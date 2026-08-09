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

namespace Ampache\Gui\Sidebar;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Upload;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\MoodRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Override;

/**
 * Resolves the viewer's access once, so the header and both ajax refresh paths build the same sidebar.
 */
final readonly class SidebarViewFactory implements SidebarViewFactoryInterface
{
    public function __construct(
        private VideoRepositoryInterface $videoRepository,
        private FolderRepositoryInterface $folderRepository,
        private MoodRepositoryInterface $moodRepository,
    ) {}

    #[Override]
    public function createSidebarView(string $activeTab): SidebarView
    {
        $user = Core::get_global('user');

        return new SidebarView(
            AmpConfig::get_web_path('/client'),
            AmpConfig::get_web_path('/admin'),
            (AmpConfig::get('album_group')) ? 'album' : 'album_disk',
            $this->videoRepository,
            $this->folderRepository,
            $this->moodRepository,
            $activeTab,
            (string) Session::get(),
            User::is_registered(),
            User::is_registered() && (($user instanceof User) ? $user->getId() : 0) > 0,
            Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
            Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER),
            Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN),
            Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::GUEST),
            Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER),
            (bool) AmpConfig::get('allow_localplay_playback')
                && (bool) AmpConfig::get('localplay_controller')
                && Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::GUEST),
            Upload::can_upload($user)
        );
    }
}
