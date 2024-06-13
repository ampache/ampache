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

namespace Ampache\Module\Application\Preferences;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateUserAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'update_user';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private RequestParserInterface $requestParser;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        RequestParserInterface $requestParser
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
        $this->requestParser   = $requestParser;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            (
                $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false &&
                (int)(Core::get_global('user')?->getId()) > 0
            ) ||
            !$this->requestParser->verifyForm('update_user')
        ) {
            throw new AccessDeniedException();
        }
        // block updates from simple users
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SIMPLE_USER_MODE) === true) {
            throw new AccessDeniedException();
        }
        $user = Core::get_global('user');
        if (!$user instanceof User) {
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        // Remove the value
        unset($_SESSION['forms']['account']);

        // Don't let them change access, or username here
        unset($_POST['access']);
        $_POST['username'] = $user->username;

        $mandatory_fields = (array) AmpConfig::get('registration_mandatory_fields');
        if (in_array('fullname', $mandatory_fields) && !$_POST['fullname']) {
            AmpError::add('fullname', T_("Please fill in your full name (first name, last name)"));
        }
        if (in_array('website', $mandatory_fields) && !$_POST['website']) {
            AmpError::add('website', T_("Please fill in your website"));
        }
        if (in_array('state', $mandatory_fields) && !$_POST['state']) {
            AmpError::add('state', T_("Please fill in your state"));
        }
        if (in_array('city', $mandatory_fields) && !$_POST['city']) {
            AmpError::add('city', T_("Please fill in your city"));
        }

        $this->ui->showHeader();
        /** @see User::update() */
        if (!$user->update($_POST)) {
            AmpError::add('general', T_('Update failed'));
        } else {
            $user->upload_avatar();
            display_notification(T_('User updated successfully'));
        }

        $user = $gatekeeper->getUser();
        if ($user) {
            $this->ui->show(
                'show_preferences.inc.php',
                [
                    'fullname' => $user->fullname,
                    'preferences' => $user->get_preferences($_REQUEST['tab']),
                    'ui' => $this->ui
                ]
            );
        }
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
