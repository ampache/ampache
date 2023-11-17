<?php

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

declare(strict_types=0);

namespace Ampache\Module\Application\Preferences;

use Ampache\Repository\Model\Preference;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\PreferencesFromRequestUpdaterInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdatePreferencesAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'update_preferences';

    private PreferencesFromRequestUpdaterInterface $preferencesFromRequestUpdater;

    private UiInterface $ui;

    public function __construct(
        PreferencesFromRequestUpdaterInterface $preferencesFromRequestUpdater,
        UiInterface $ui
    ) {
        $this->preferencesFromRequestUpdater = $preferencesFromRequestUpdater;
        $this->ui                            = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            (
                Core::get_post('method') == 'admin' &&
                $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false
            ) ||
            !Core::form_verify('update_preference')
        ) {
            throw new AccessDeniedException();
        }

        $system = false;
        /* Reset the Theme */
        if (Core::get_post('method') == 'admin') {
            $user_id            = '-1';
            $system             = true;
            $fullname           = T_('Server');
            $_REQUEST['action'] = 'admin';
        } else {
            $user_id  = Core::get_global('user')->id;
            $fullname = Core::get_global('user')->fullname;
        }

        /* Update and reset preferences */
        $this->preferencesFromRequestUpdater->update((int) $user_id);
        Preference::init();

        // Reset gettext so that it's clear whether the preference took
        // FIXME: do we need to do any header fiddling?
        load_gettext();

        if (Core::get_post('method') == 'admin') {
            $notification_text = T_('Server preferences updated successfully');
        } else {
            $notification_text = T_('User preferences updated successfully');
        }

        $user = $gatekeeper->getUser();

        $this->ui->showHeader();

        if (!empty($notification_text)) {
            display_notification($notification_text);
        }

        // Show the default preferences page
        $this->ui->show(
            'show_preferences.inc.php',
            [
                'fullname' => $fullname,
                'preferences' => $user->get_preferences($_REQUEST['tab'], $system),
                'ui' => $this->ui
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
