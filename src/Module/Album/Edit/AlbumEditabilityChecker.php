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

namespace Ampache\Module\Album\Edit;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Override;

/**
 * A content manager may edit any album; otherwise it has to be an upload the viewer owns, and uploaders
 * must be allowed to edit at all.
 */
final readonly class AlbumEditabilityChecker implements AlbumEditabilityCheckerInterface
{
    public function __construct(
        private PrivilegeCheckerInterface $privilegeChecker,
        private ConfigContainerInterface $configContainer,
    ) {}

    #[Override]
    public function check(GuiGatekeeperInterface $gatekeeper, Album|AlbumDisk $album): bool
    {
        if ($this->privilegeChecker->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) {
            return true;
        }

        $albumArtist = ($album instanceof Album) ? $album->getAlbumArtist() : (int) $album->album_artist;
        if ($albumArtist === 0) {
            return false;
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT) === false) {
            return false;
        }

        return $album->get_user_owner() === $gatekeeper->getUserId();
    }
}
