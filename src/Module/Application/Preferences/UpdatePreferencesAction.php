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

namespace Ampache\Module\Application\Preferences;

use Ampache\Gui\Preferences\PreferenceSubject;
use Ampache\Gui\Preferences\PreferencesViewFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\Preference;
use Ampache\Module\System\PreferencesFromRequestUpdaterInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdatePreferencesAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'update_preferences';

    public function __construct(
        private PreferencesViewFactoryInterface $preferencesViewFactory,
        private PreferencesFromRequestUpdaterInterface $preferencesFromRequestUpdater,
        private UiInterface $ui,
        private RequestParserInterface $requestParser,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $isServer = Core::get_post('method') === 'admin';
        $user     = $gatekeeper->getUser();

        // demo mode answers every level check with true, so it is refused here rather than three layers down
        if (
            !$user instanceof User
            || $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false
            || $gatekeeper->isDemoMode()
            || ($isServer && !$gatekeeper->mayAdminister())
            || !$this->requestParser->verifyForm('update_preference')
        ) {
            throw new AccessDeniedException();
        }

        $this->preferencesFromRequestUpdater->update($isServer ? User::INTERNAL_SYSTEM_USER_ID : $user->getId());
        Preference::init();

        // Reset gettext so that it's clear whether the preference took
        load_gettext();

        $this->ui->showHeader();
        display_notification($isServer ? T_('Server preferences updated successfully') : T_('User preferences updated successfully'));

        echo $this->preferencesViewFactory->create(
            $gatekeeper,
            $isServer ? PreferenceSubject::serverPreferences($user) : PreferenceSubject::ownPreferences($user),
            $user,
            (string) (((array) $request->getParsedBody())['tab'] ?? '')
        )->render();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
