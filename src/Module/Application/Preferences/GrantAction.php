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

namespace Ampache\Module\Application\Preferences;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GrantAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'grant';

    private RequestParserInterface $requestParser;

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        RequestParserInterface $requestParser,
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        $this->requestParser   = $requestParser;
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

        if ($user !== null) {
            $plugin_name = mb_strtolower($this->requestParser->getFromRequest('plugin'));
            if (
                $this->requestParser->getFromRequest('token') &&
                in_array($plugin_name, Plugin::get_plugins('save_mediaplay'))
            ) {
                // we receive a token for a valid plugin, have to call getSession and obtain a session key
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin !== null) {
                    $plugin->load($user);
                    if ($plugin->_plugin->get_session($this->requestParser->getFromRequest('token'))) {
                        $title = T_('No Problem');
                        $text  = T_('Your account has been updated') . ' : ' . $plugin_name;
                    } else {
                        $title = T_('There Was a Problem');
                        $text  = T_('Your account has not been updated') . ' : ' . $plugin_name;
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
                    'preferences' => $user->get_preferences($this->requestParser->getFromRequest('tab')),
                    'ui' => $this->ui,
                ]
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
