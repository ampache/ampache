<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Application\Preferences;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GrantAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'grant';

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
        $user = $gatekeeper->getUser();

        // Make sure we're a user and they came from the form
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) === false &&
            !isset($user->id)
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $pluginName = mb_strtolower(Core::get_request('plugin'));

        if (
            Core::get_request('token') &&
            in_array($pluginName, Plugin::get_plugins('save_mediaplay'))
        ) {
            // we receive a token for a valid plugin, have to call getSession and obtain a session key
            if ($plugin = new Plugin($pluginName)) {
                $plugin->load($user);
                if ($plugin->_plugin->get_session(Core::get_request('token'))) {
                    $title    = T_('No Problem');
                    $text     = T_('Your account has been updated') . ' : ' . $pluginName;
                } else {
                    $title    = T_('There Was a Problem');
                    $text     = T_('Your account has not been updated') . ' : ' . $pluginName;
                }
                $next_url = sprintf(
                    '%s/preferences.php?tab=plugins',
                    $this->configContainer->getWebPath()
                );

                $this->ui->showConfirmation($title, $text, $next_url);

                return null;
            }
        }

        $this->ui->show(
            'show_preferences.inc.php',
            [
                'fullname' => $user->fullname,
                'preferences' => $user->get_preferences($_REQUEST['tab']),
                'ui' => $this->ui,
            ]
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
