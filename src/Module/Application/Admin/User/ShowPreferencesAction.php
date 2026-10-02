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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Gui\Preferences\PreferenceSubject;
use Ampache\Gui\Preferences\PreferencesViewFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders the preferences of another account, on the same screen an account gets for its own.
 */
final readonly class ShowPreferencesAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show_preferences';

    public function __construct(
        private UiInterface $ui,
        private ModelFactoryInterface $modelFactory,
        private PreferencesViewFactoryInterface $preferencesViewFactory,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$gatekeeper->mayAdminister()) {
            throw new AccessDeniedException();
        }

        $operator = $gatekeeper->getUser();
        if (!$operator instanceof User) {
            throw new AccessDeniedException();
        }

        $userId = (int) ($request->getQueryParams()['user_id'] ?? 0);
        $target = $this->modelFactory->createUser($userId);
        if ($target->isNew()) {
            throw new ObjectNotFoundException($userId);
        }

        // the account form always writes the signed-in account, so it must not appear under someone else's title
        $tab = (string) ($request->getQueryParams()['tab'] ?? 'interface');
        if ($tab === 'account' || $tab === 'quickconnect') {
            $tab = 'interface';
        }

        $this->ui->showHeader();
        echo $this->preferencesViewFactory->create(
            $gatekeeper,
            PreferenceSubject::otherUser($target, $operator),
            $operator,
            $tab
        )->render();
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
