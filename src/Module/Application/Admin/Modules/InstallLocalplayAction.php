<?php

declare(strict_types=0);

/**
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

namespace Ampache\Module\Application\Admin\Modules;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class InstallLocalplayAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'install_localplay';

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
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) === false) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $localplay = new LocalPlay(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));
        if (!$localplay->player_loaded()) {
            AmpError::add('general', T_('Failed to enable the Localplay module'));
            echo AmpError::display('general');

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }
        // Install it!
        $localplay->install();

        // Go ahead and enable Localplay (Admin->System) as we assume they want to do that
        // if they are enabling this
        Preference::update('allow_localplay_playback', -1, '1');
        Preference::update('localplay_level', Core::get_global('user')?->getId(), AccessLevelEnum::ADMIN->value);
        Preference::update('localplay_controller', Core::get_global('user')?->getId(), $localplay->type);

        /* Show Confirmation */
        $url   = sprintf('%s/admin/modules.php?action=show_localplay', $this->configContainer->getWebPath());
        $title = T_('No Problem');
        $body  = T_('Localplay has been enabled');
        $this->ui->showConfirmation($title, $body, $url);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
